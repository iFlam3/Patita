"""
Descarga fotos de mascotas con licencia libre desde Wikimedia Commons usando Scrapling.
(Las fotos de productos son propias: public/assets/images/products/.)

- Solo acepta CC0, dominio público, CC BY y CC BY-SA (permiten uso con atribución).
- Se identifica con un User-Agent propio: no se hace pasar por un navegador ni
  evade protecciones.
- Guarda las fotos en public/assets/images/photos/ y los créditos (autor,
  licencia y enlace) en app/config/photo_credits.php, que muestra la web.

Uso:  python scripts/fetch_photos.py            (solo lo que falte)
      python scripts/fetch_photos.py --force    (vuelve a descargar todo)
Requisito: pip install "scrapling[fetchers]"
"""
import html
import json
import re
import sys
import time
from pathlib import Path

from scrapling.fetchers import Fetcher

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "public" / "assets" / "images" / "photos"
CREDITS = ROOT / "app" / "config" / "photo_credits.php"
API = "https://commons.wikimedia.org/w/api.php"
HEADERS = {"User-Agent": "PatitaSchoolProject/1.0 (proyecto educativo; https://github.com/iFlam3/Patita)"}
# Evita fotos médicas o desagradables que la búsqueda a veces devuelve.
BLOCKED = re.compile(r"disease|lesion|wound|dead|injur|surgery|necro|skull|x-ray|parasite|tumou?r|carcass|blood", re.I)
ALLOWED = re.compile(r"^(CC0|Public domain|PD|CC BY(-SA)? \d(\.\d)?)", re.I)

# clave de archivo -> búsqueda en Commons (en inglés da más resultados)
QUERIES = {
    "pet-dog": "golden retriever dog", "pet-cat": "domestic cat portrait", "pet-bird": "Serinus canaria",
    "pet-fish": "goldfish aquarium", "pet-reptile": "bearded dragon pogona", "pet-horse": "horse portrait",
    "pet-small_pet": "domestic rabbit",
}


# Archivos concretos elegidos a mano cuando la búsqueda por texto no acierta.
PINNED = {
    "pet-bird": "File:Yellow Canary (Crithagra flaviventris) (6536890761).jpg",
}
# Si no hay foto propia, se reutiliza la de otra clave (con su mismo crédito).
FALLBACK: dict = {}


def clean(value: str) -> str:
    return html.unescape(re.sub(r"<[^>]+>", "", value or "")).strip()


def search(query: str, used: set, pinned: str = ""):
    target = {"titles": pinned} if pinned else {
        "generator": "search", "gsrnamespace": "6", "gsrsearch": f"{query} filetype:bitmap", "gsrlimit": "15"}
    page = Fetcher.get(API, params={
        "action": "query", "format": "json", **target, "prop": "imageinfo",
        "iiprop": "url|extmetadata|mime|size", "iiurlwidth": "800",
    }, headers=HEADERS, stealthy_headers=False, impersonate=None, timeout=30)
    data = json.loads(page.body)
    pages = sorted((data.get("query") or {}).get("pages", {}).values(), key=lambda p: p.get("index", 99))
    for item in pages:
        info = (item.get("imageinfo") or [{}])[0]
        meta = info.get("extmetadata", {})
        license_name = clean(meta.get("LicenseShortName", {}).get("value", ""))
        if info.get("mime") != "image/jpeg" or not ALLOWED.match(license_name) or BLOCKED.search(item["title"]) \
                or item["title"].removeprefix("File:") in used:
            continue
        width, height = info.get("width", 0), info.get("height", 0)
        if width < 600 or not (0.9 <= width / max(height, 1) <= 2.0):  # horizontales o casi cuadradas
            continue
        return {
            "thumb": info["thumburl"],
            "title": item["title"].removeprefix("File:"),
            "author": clean(meta.get("Artist", {}).get("value", "")) or "Autor desconocido",
            "license": license_name,
            "license_url": meta.get("LicenseUrl", {}).get("value", ""),
            "source": info.get("descriptionurl", ""),
        }
    return None


def php_export(credits: dict) -> str:
    def s(v):
        return "'" + str(v).replace("\\", "\\\\").replace("'", "\\'") + "'"
    lines = ["<?php", "", "declare(strict_types=1);", "",
             "// Generado por scripts/fetch_photos.py: créditos de fotos de Wikimedia Commons.",
             "return ["]
    for path, c in sorted(credits.items()):
        fields = ", ".join(f"{s(k)} => {s(c[k])}" for k in ("title", "author", "license", "license_url", "source"))
        lines.append(f"    {s(path)} => [{fields}],")
    lines.append("];")
    return "\n".join(lines) + "\n"


def main() -> None:
    sys.stdout.reconfigure(encoding="utf-8")
    force = "--force" in sys.argv
    OUT.mkdir(parents=True, exist_ok=True)
    credits = {}
    if CREDITS.exists() and not force:
        # Conserva los créditos previos de fotos que ya están descargadas.
        for m in re.finditer(r"'(assets/images/photos/[^']+)' => \[(.*?)\],", CREDITS.read_text(encoding="utf-8")):
            fields = dict(re.findall(r"'(\w+)' => '((?:[^'\\]|\\.)*)'", m.group(2)))
            credits[m.group(1)] = {k: v.replace("\\'", "'").replace("\\\\", "\\") for k, v in fields.items()}
    missing = []
    for key, query in QUERIES.items():
        rel = f"assets/images/photos/{key}.jpg"
        if not force and (OUT / f"{key}.jpg").exists():
            continue
        used = {c["title"] for p, c in credits.items() if p != rel}
        found = search(query, used, PINNED.get(key, ""))
        time.sleep(1)
        if not found:
            missing.append(key)
            print(f"  sin resultado con licencia libre: {key} ({query})")
            continue
        image = Fetcher.get(found.pop("thumb"), headers=HEADERS, stealthy_headers=False, impersonate=None, timeout=60)
        if image.status != 200 or not image.body.startswith(b"\xff\xd8"):
            missing.append(key)
            print(f"  descarga fallida: {key}")
            continue
        (OUT / f"{key}.jpg").write_bytes(image.body)
        credits[rel] = found
        CREDITS.write_text(php_export(credits), encoding="utf-8", newline="\n")
        print(f"  {key}: {found['title']} ({found['license']}, {found['author'][:40]})")
        time.sleep(1)
    for key, other in FALLBACK.items():
        src, rel = OUT / f"{other}.jpg", f"assets/images/photos/{key}.jpg"
        if not (OUT / f"{key}.jpg").exists() and src.exists():
            (OUT / f"{key}.jpg").write_bytes(src.read_bytes())
            credits[rel] = credits[f"assets/images/photos/{other}.jpg"]
            missing = [m for m in missing if m != key]
    credits = {p: c for p, c in credits.items() if (ROOT / "public" / p).exists()}
    CREDITS.write_text(php_export(credits), encoding="utf-8", newline="\n")
    print(f"Fotos con crédito: {len(credits)}. Sin foto: {len(missing)} {missing}")


if __name__ == "__main__":
    main()
