"""Citation validator: every claim ({"text", "sources"}) must cite chunk ids that were retrieved."""


def claims(node):
    if isinstance(node, dict):
        if "text" in node:
            yield node
        for v in node.values():
            yield from claims(v)
    elif isinstance(node, list):
        for v in node:
            yield from claims(v)


def validate(data, known_ids):
    errors = []
    for c in claims(data):
        src = c.get("sources")
        if not isinstance(src, list) or not src:
            errors.append(f"uncited: {c['text'][:60]}")
            continue
        errors += [f"unknown source {s!r}: {c['text'][:60]}" for s in src if s not in known_ids]
    return {"ok": not errors, "errors": errors}
