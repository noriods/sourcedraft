"""One OpenAI-compatible chat client (OpenRouter, Azure OpenAI, Ollama/vLLM, Baseten)."""
import json
import os
import urllib.request
from urllib.parse import urlparse

from . import ROOT

LOCAL_HOSTS = {"localhost", "127.0.0.1", "::1"}


def check_privacy(base_url, privacy):
    host = urlparse(base_url).hostname
    if privacy == "local-only" and host not in LOCAL_HOSTS:
        raise PermissionError(f"privacy is local-only but provider host is {host}")


def _load_env():
    env = ROOT / ".env"
    if env.exists():
        for line in env.read_text().splitlines():
            if "=" in line and not line.startswith("#"):
                k, v = line.split("=", 1)
                os.environ.setdefault(k.strip(), v.strip())


def chat(cfg, messages, privacy="any"):
    """cfg: base_url, model, api_key_env. Returns the reply text."""
    check_privacy(cfg["base_url"], privacy)
    _load_env()
    key = os.environ.get(cfg.get("api_key_env", ""), "")
    req = urllib.request.Request(
        cfg["base_url"].rstrip("/") + "/chat/completions",
        json.dumps({"model": cfg["model"], "messages": messages}).encode(),
        {"Content-Type": "application/json", "Authorization": f"Bearer {key}"},
    )
    with urllib.request.urlopen(req, timeout=300) as r:
        return json.load(r)["choices"][0]["message"]["content"]
