# Guide

## What it is
A small tool that reads a client's own documents and writes first drafts of slides and case studies from them. Each point is tied to the page it came from, a checker rejects anything that is not, and a person must approve before a clean file exists. Think "drafting assistant that shows its sources", not "chatbot".

## Start a new project (another bid, a client, or our own stuff)
1. Copy the demo folder: `cp -r projects/demo-management projects/<new-name>` and delete its `index.db`.
2. Replace the files in `projects/<new-name>/sources/` (pptx, pdf, docx, md, txt).
3. Edit `projects/<new-name>/project.yaml`: model, provider, `privacy`, template. Private client = `privacy: local-only` with a local model, or a cloud tenant URL.
4. Optional: put the client's own pptx/docx in `templates/` and point `project.yaml` at it.
5. Optional: new recipe = copy a folder in `recipes/` and edit its three files (prompt, JSON shape, queries/exporter).

## Commands
```bash
. .venv/bin/activate
python -m sourcedraft ingest <project>
python -m sourcedraft draft <project> teaching-deck --topic "Managerial decision making"
python -m sourcedraft draft <project> case-vignette --topic "Managerial decision making"
python -m sourcedraft review <run>
python -m sourcedraft approve <run> --by "Name"
python -m sourcedraft runs <project>
```
Final files are only created by `approve`. Draft files have a DRAFT watermark. A run that fails the citation check says why in `validation.json`.

## What to say about it
- Every bullet carries its source. Open the speaker notes or the source line.
- Nothing is made up: unknown or missing citations fail the run.
- A human approves by name, and it is logged.
- Works with a model that never leaves the client's machine.
- Output is normal pptx/docx, so faculty edit it in Office as usual.
- Honest limit: it drafts from the documents given. It will not fill gaps, and search is keyword-based for now.

## Cost
The demo model is cheap (`qwen/qwen3.8-flash` on OpenRouter): a deck is a few cents.
