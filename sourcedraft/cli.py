import argparse
import json

from . import ROOT, runs, validate


def review(d):
    status, manifest, valid = runs.read(d, "status.json"), runs.read(d, "manifest.json"), runs.read(d, "validation.json")
    print(f"{d.name}: {status['status']} | model {manifest['model']} | topic: {manifest['topic']}")
    print("validation:", "ok" if valid["ok"] else "FAILED")
    for e in valid["errors"]:
        print("  -", e)
    model = runs.read(d, "model.json")["parsed"]
    used = {s for c in validate.claims(model) for s in c["sources"]} if model else set()
    for c in runs.read(d, "retrieved.json"):
        if c["id"] in used:
            print(f"\n[{c['id']}] {c['text'][:300]}")


def main():
    p = argparse.ArgumentParser(prog="sd")
    sub = p.add_subparsers(dest="cmd", required=True)
    sub.add_parser("ingest").add_argument("project")
    dr = sub.add_parser("draft")
    dr.add_argument("project"), dr.add_argument("recipe"), dr.add_argument("--topic", required=True)
    sub.add_parser("review").add_argument("run")
    ap = sub.add_parser("approve")
    ap.add_argument("run"), ap.add_argument("--by", required=True)
    sub.add_parser("runs").add_argument("project")
    a = p.parse_args()
    if a.cmd == "ingest":
        print(runs.ingest_project(a.project), "chunks indexed")
    elif a.cmd == "draft":
        d = runs.draft(a.project, a.recipe, a.topic)
        print(d.relative_to(ROOT / "runs"), runs.read(d, "status.json")["status"])
    elif a.cmd == "review":
        review(runs.run_dir(a.run))
    elif a.cmd == "approve":
        runs.approve(runs.run_dir(a.run), a.by)
        print("approved and exported final")
    elif a.cmd == "runs":
        for d in sorted((ROOT / "runs" / a.project).glob("*")):
            print(f"{a.project}/{d.name}  {runs.read(d, 'status.json')['status']}")
