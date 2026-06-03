"""Per-site build manifest: resumable, idempotent unit tracking."""
from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Optional, Union


class Manifest:
    def __init__(self, path: Union[str, Path]):
        self.path = Path(path)
        self.data: dict[str, Any] = {"domain": None, "phase": None, "units": {}, "articles": {}}
        if self.path.exists():
            self.data = json.loads(self.path.read_text(encoding="utf-8"))

    def save(self) -> None:
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self.path.write_text(
            json.dumps(self.data, indent=2, ensure_ascii=False), encoding="utf-8"
        )

    def set_unit(self, key: str, status: str) -> None:
        self.data["units"][key] = status
        self.save()

    def unit_status(self, key: str) -> Optional[str]:
        return self.data["units"].get(key)

    def is_done(self, key: str) -> bool:
        return self.data["units"].get(key) == "done"

    def set_meta(self, key: str, value: Any) -> None:
        self.data[key] = value
        self.save()
