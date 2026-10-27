"""Project config, run folders, status/audit and the approval gate."""
import hashlib
import json
from datetime import datetime, timezone
from pathlib import Path

import yaml

from . import ROOT, export_docx, export_pptx, index, ingest, provider
from .validate import validate


def now():
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


def load_project(name):
    cfg = yaml.safe_load((ROOT / "projects" / name / "project.yaml").read_text())
    cfg["dir"] = ROOT / "projects" / name
    cfg["name"] = name
    return cfg


def load_recipe(name):
    d = ROOT / "recipes" / name
    return {**yaml.safe_load((d / "recipe.yaml").read_text()), "name": name,
            "prompt": (d / "prompt.md").read_text(), "schema": (d / "schema.json").read_text()}


def run_dir(run):
    return ROOT / "runs" / run


def read(d, name):
    return json.loads((d / name).read_text())


def write(d, name, obj):
    (d / name).write_text(json.dumps(obj, indent=2))


def audit(d, event, **info):
    with open(d / "audit.jsonl", "a") as f:
        f.write(json.dumps({"at": now(), "event": event, **info}) + "\n")


def ingest_project(project):
    cfg = load_project(project)
    chunks = ingest.ingest_dir(cfg["dir"] / cfg.get("sources", "sources"))
    index.build(cfg["dir"] / "index.db", chunks)
    return len(chunks)


def _template(cfg, kind):
    t = cfg.get("template", {}).get(kind)
    return str(cfg["dir"] / t) if t else str(ROOT / "templates" / f"default.{kind}")


def export(d, final=False):
    """Export draft or final files for a run. Final needs an approval record."""
    status = read(d, "status.json")
    if final and not status.get("approval"):
        raise PermissionError("final export needs an approval record")
    data = read(d, "model.json")["parsed"]
    manifest = read(d, "manifest.json")
    cfg = load_project(manifest["project"])
    if manifest["exporter"] == "pptx":
        export_pptx.export(data, d / ("final.pptx" if final else "draft.pptx"), _template(cfg, "pptx"), final)
    else:
        export_docx.export(data, d, _template(cfg, "docx"), final)


def parse_json(text):
    text = text.strip()
    if text.startswith("```"):
        text = text.split("\n", 1)[1].rsplit("```", 1)[0]
    return json.loads(text)


def draft(project, recipe_name, topic, chat=provider.chat):
    cfg, recipe = load_project(project), load_recipe(recipe_name)
    db = cfg["dir"] / "index.db"
    found = {}
    for q in recipe["queries"]:
        for c in index.search(db, q.replace("{topic}", topic), recipe.get("k", 8)):
            found[c["id"]] = c
    d = ROOT / "runs" / project / f"{datetime.now(timezone.utc):%Y%m%d-%H%M%S}-{recipe_name}"
    d.mkdir(parents=True)
    src = cfg["dir"] / cfg.get("sources", "sources")
    write(d, "manifest.json", {
        "project": project, "recipe": recipe_name, "exporter": recipe["exporter"], "topic": topic,
        "model": cfg["provider"]["model"], "privacy": cfg.get("privacy", "any"),
        "inputs": {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(src.rglob("*")) if p.is_file()},
        "prompt_sha256": hashlib.sha256(recipe["prompt"].encode()).hexdigest(),
    })
    write(d, "retrieved.json", list(found.values()))
    write(d, "status.json", {"status": "draft"})
    audit(d, "created", topic=topic)
    sources = "\n\n".join(f"[{c['id']}]\n{c['text']}" for c in found.values())
    prompt = recipe["prompt"].replace("{topic}", topic).replace("{schema}", recipe["schema"]).replace("{sources}", sources)
    raw = chat(cfg["provider"], [{"role": "user", "content": prompt}], cfg.get("privacy", "any"))
    audit(d, "model_called", model=cfg["provider"]["model"])
    try:
        parsed = parse_json(raw)
        result = validate(parsed, set(found))
    except ValueError as e:
        parsed, result = None, {"ok": False, "errors": [f"model output is not JSON: {e}"]}
    write(d, "model.json", {"raw": raw, "parsed": parsed})
    write(d, "validation.json", result)
    if result["ok"]:
        export(d)
        audit(d, "draft_exported")
    else:
        write(d, "status.json", {"status": "failed"})
        audit(d, "validation_failed", errors=result["errors"])
    return d


def approve(d, by):
    if not by.strip():
        raise ValueError("approval needs a name")
    if not read(d, "validation.json")["ok"]:
        raise PermissionError("cannot approve a run that failed validation")
    write(d, "status.json", {"status": "approved", "approval": {"by": by, "at": now()}})
    audit(d, "approved", by=by)
    export(d, final=True)
    status = read(d, "status.json")
    write(d, "status.json", {**status, "status": "final"})
    audit(d, "final_exported")
