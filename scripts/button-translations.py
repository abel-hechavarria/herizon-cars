#!/usr/bin/env python3
"""Merge the HCars Button strings into hu_HU PO/MO/POT and Gutenberg script JSON."""
from __future__ import annotations

import ast
import json
import re
import struct
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
LANG = ROOT / "languages"
DOMAIN = "herizon-cars"
LOCALE = "hu_HU"
EDITOR_HANDLE = "herizon-cars-button-editor"

HU = {
    "Three customizable button variants with optional icons.": "Három testreszabható gombváltozat, választható ikonokkal.",
    "Accessible label": "Akadálymentesítési címke",
    "Accepts page links and anchors such as #contact.": "Oldalhivatkozás és horgony is megadható, például: #contact.",
    "Alignment": "Igazítás",
    "Auto": "Automatikus",
    "Background": "Háttér",
    "Blue": "Kék",
    "Blue gradient": "Kék színátmenet",
    "Bold": "Félkövér",
    "Border color": "Szegély színe",
    "Border style": "Szegély stílusa",
    "Border width": "Szegély vastagsága",
    "Blur": "Elmosás",
    "Button": "Gomb",
    "Button text": "Gomb felirata",
    "Capitalize": "Szavak kezdőbetűje nagy",
    "Center": "Középre",
    "Choose a CSS length, such as 16px or 1rem.": "Adjon meg CSS-hosszt, például 16px vagy 1rem.",
    "Choose icon": "Ikon kiválasztása",
    "Choose SVG": "SVG kiválasztása",
    "Close icon picker": "Ikonválasztó bezárása",
    "Content": "Tartalom",
    "Corner radius": "Sarok lekerekítése",
    "Custom": "Egyéni",
    "Custom shadow": "Egyéni árnyék",
    "Custom SVG": "Egyéni SVG",
    "Custom width": "Egyéni szélesség",
    "Dashed": "Szaggatott",
    "Design": "Megjelenés",
    "Design and icon": "Megjelenés és ikon",
    "Desktop": "Asztali",
    "Device": "Eszköz",
    "Dotted": "Pontozott",
    "Double": "Kettős",
    "Email": "E-mail",
    "Empty overrides inherit from larger devices and the selected preset. Preview follows the device and state selected here.": "Az üres felülbírálások a nagyobb eszközöktől és a kiválasztott előbeállítástól öröklik az értéket. Az előnézet a kiválasztott eszközt és állapotot követi.",
    "Expand image %d": "%d. kép kibontása",
    "Expanded width": "Kibontott szélesség",
    "Fade edges": "Szélek elhalványítása",
    "Filled circle": "Kitöltött kör",
    "Fit content": "Tartalomhoz igazítás",
    "Font Awesome library": "Font Awesome ikonkészlet",
    "Font family": "Betűcsalád",
    "Font family names, for example Saira, sans-serif. Empty uses the preset.": "Betűcsalád neve, például Saira, sans-serif. Üresen hagyva az előbeállítás érvényesül.",
    "Font size": "Betűméret",
    "Font weight": "Betűvastagság",
    "Full width": "Teljes szélesség",
    "HCars Button": "HCars gomb",
    "Hero selector": "Hero CSS-szelektor",
    "Horizontal offset": "Vízszintes eltolás",
    "Hover": "Hover",
    "Hover / keyboard focus": "Hover / billentyűzet-fókusz",
    "Hover movement (px)": "Elmozdulás hoverkor (px)",
    "Icon": "Ikon",
    "Icon background": "Ikon háttere",
    "Icon border color": "Ikon szegélyének színe",
    "Icon border style": "Ikon szegélyének stílusa",
    "Icon border width": "Ikon szegélyének vastagsága",
    "Icon container": "Ikontartó",
    "Icon container diameter": "Ikontartó átmérője",
    "Icon color": "Ikon színe",
    "Icon corner radius": "Ikon sarkának lekerekítése",
    "Icon hover movement (px)": "Ikon elmozdulása hoverkor (px)",
    "Icon library": "Ikonkészlet",
    "Icon position": "Ikon pozíciója",
    "Icon padding": "Ikon belső margója",
    "Icon shadow": "Ikon árnyéka",
    "Icon size": "Ikon mérete",
    "Icon source": "Ikon forrása",
    "Image": "Kép",
    "Image height": "Kép magassága",
    "Images": "Képek",
    "Inherit": "Öröklés",
    "Inherited:": "Örökölt:",
    "Item width": "Elem szélessége",
    "Layout": "Elrendezés",
    "Left": "Balra",
    "Letter spacing": "Betűköz",
    "Line height": "Sorköz",
    "Link": "Hivatkozás",
    "Link rel": "Hivatkozás rel attribútuma",
    "Link relationship (rel)": "Hivatkozás rel attribútuma",
    "Link URL": "Hivatkozás URL-je",
    "Local icon preview": "Ikon helyi előnézete",
    "Logo height": "Logó magassága",
    "Logos": "Logók",
    "Maximum width": "Maximális szélesség",
    "Media library (SVG)": "Médiatár (SVG)",
    "Mobile — up to 767 px": "Mobil — 767 px-ig",
    "Modules": "Modulok",
    "None": "Nincs",
    "Normal": "Normál",
    "Normal and hover styles": "Normál és hover stílus",
    "Lowercase": "Kisbetűs",
    "Open in a new tab": "Megnyitás új lapon",
    "Open in new tab": "Megnyitás új lapon",
    "Optional. Use only when the visible text does not describe the destination.": "Nem kötelező. Csak akkor adja meg, ha a látható felirat nem írja le a hivatkozás célját.",
    "Outlined circle": "Kör alakú, körvonalas",
    "Outline": "Körvonalas",
    "Padding bottom": "Alsó belső margó",
    "Padding left": "Bal oldali belső margó",
    "Padding right": "Jobb oldali belső margó",
    "Padding top": "Felső belső margó",
    "Pause animation": "Animáció szüneteltetése",
    "Pause on hover": "Szüneteltetés hoverkor",
    "Pill": "Kapszula",
    "Play": "Lejátszás",
    "Preset": "Előbeállítás",
    "Remove SVG": "SVG eltávolítása",
    "Replace SVG": "SVG cseréje",
    "Reset design overrides": "Megjelenés felülbírálásainak visszaállítása",
    "Reset style overrides": "Stílusfelülbírálások visszaállítása",
    "Reset styles": "Stílusok visszaállítása",
    "Resume animation": "Animáció folytatása",
    "Right": "Jobbra",
    "SVG icon": "SVG-ikon",
    "Search icons": "Ikonok keresése",
    "Separator": "Elválasztó",
    "Separator color": "Elválasztó színe",
    "Shadow": "Árnyék",
    "Show icon": "Ikon megjelenítése",
    "Showing up to 60 matches. Refine the search for more icons.": "Legfeljebb 60 találat látható. További ikonokhoz pontosítsa a keresést.",
    "Smooth scrolling": "Finom görgetés",
    "Solid": "Folytonos",
    "Spacing": "Térköz",
    "Spread": "Kiterjedés",
    "State": "Állapot",
    "Style": "Stílus",
    "Tablet — up to 1024 px": "Tablet — 1024 px-ig",
    "Text": "Szöveg",
    "Text / icon spacing": "Szöveg és ikon közötti térköz",
    "Text color": "Szöveg színe",
    "Text transformation": "Szöveg átalakítása",
    "The icon library could not be loaded. Your selected icon is preserved.": "Az ikonkészlet nem tölthető be. A kiválasztott ikon megmarad.",
    "Transition duration (ms)": "Átmenet időtartama (ms)",
    "Transparent": "Átlátszó",
    "Use inherited value": "Örökölt érték használata",
    "Use preset / inherit": "Előbeállítás / öröklés használata",
    "Use the Elementor accent font": "Az Elementor kiemelő betűtípusának használata",
    "Use icon container": "Ikontartó használata",
    "URL": "URL",
    "Uppercase": "Nagybetűs",
    "Variant": "Változat",
    "Vertical offset": "Függőleges eltolás",
    "Visible images": "Látható képek",
    "Width": "Szélesség",
    "Width: auto, 100% or length": "Szélesség: automatikus, 100% vagy megadott érték",
    "100": "100",
    "200": "200",
    "300": "300",
    "400": "400",
    "500": "500",
    "600": "600",
    "700": "700",
    "800": "800",
    "900": "900",
}


def decode_po_quoted(value: str) -> str:
    return ast.literal_eval(value)


def parse_po(path: Path) -> tuple[list[str], dict[str, str]]:
    raw_blocks = path.read_text(encoding="utf-8").strip().split("\n\n")
    entries: dict[str, str] = {}
    for block in raw_blocks:
        msgid = None
        msgstr = None
        active = None
        for line in block.splitlines():
            match = re.match(r'^(msgid|msgstr)\s+("(?:\\.|[^"])*")$', line)
            if match:
                active = match.group(1)
                if active == "msgid":
                    msgid = decode_po_quoted(match.group(2))
                else:
                    msgstr = decode_po_quoted(match.group(2))
                continue
            continuation = re.match(r'^("(?:\\.|[^"])*")$', line)
            if continuation and active:
                value = decode_po_quoted(continuation.group(1))
                if active == "msgid":
                    msgid = (msgid or "") + value
                else:
                    msgstr = (msgstr or "") + value
        if msgid is not None:
            entries[msgid] = msgstr or ""
    return raw_blocks, entries


def po_line(keyword: str, value: str) -> str:
    return f"{keyword} {json.dumps(value, ensure_ascii=False)}"


def write_catalogues() -> None:
    po_path = LANG / f"{LOCALE}.po"
    pot_path = LANG / f"{DOMAIN}.pot"
    mo_path = LANG / f"{LOCALE}.mo"
    LANG.mkdir(parents=True, exist_ok=True)
    po_blocks, po_entries = parse_po(po_path)
    pot_blocks, pot_entries = parse_po(pot_path)

    widget = (ROOT / "inc/widgets/class-widget-button.php").read_text(encoding="utf-8")
    php_strings = set(re.findall(r"""(?:__|esc_html__|esc_attr__|_e|esc_html_e)\(\s*(['"])(.*?)\1\s*,\s*['"]herizon-cars['"]""", widget))
    msgids = {value for _, value in php_strings}

    schema = json.loads((ROOT / "assets/button-schema.json").read_text(encoding="utf-8"))
    dynamic = set()
    for field in schema.get("fields", []):
        if field.get("label"):
            dynamic.add(field["label"])
        dynamic.update((field.get("options") or {}).values())
    for preset in schema.get("presets", {}).values():
        if preset.get("label"):
            dynamic.add(preset["label"])
    msgids.update(dynamic)

    editor_js = (ROOT / "src/js/button-editor.jsx").read_text(encoding="utf-8")
    js_strings = {decode_js_string(value) for _, value in re.findall(r"""\bt\(\s*(['"])((?:\\.|(?!\1).)*)\1""", editor_js)}
    msgids.update(js_strings)
    metadata = json.loads((ROOT / "blocks/button/block.json").read_text(encoding="utf-8"))
    msgids.add(metadata["description"])
    if missing := sorted(msgids.difference(HU).difference(po_entries)):
        raise SystemExit("Missing Hungarian translations: " + ", ".join(missing))

    new_po = []
    for msgid in sorted(msgids.difference(po_entries)):
        new_po.append(po_line("msgid", msgid) + "\n" + po_line("msgstr", HU[msgid]))
        po_entries[msgid] = HU[msgid]
    if new_po:
        po_blocks.extend(new_po)
        po_path.write_text("\n\n".join(po_blocks).rstrip() + "\n", encoding="utf-8")

    new_pot = []
    for msgid in sorted(msgids.difference(pot_entries)):
        new_pot.append("# HCars Button strings maintained by button-translations.py\n" + po_line("msgid", msgid) + "\nmsgstr \"\"")
        pot_entries[msgid] = ""
    if new_pot:
        pot_blocks.extend(new_pot)
        pot_path.write_text("\n\n".join(pot_blocks).rstrip() + "\n", encoding="utf-8")

    compile_mo(po_entries, mo_path)
    js_catalogue = {
        "domain": DOMAIN,
        "locale_data": {
            "messages": {
                "": {"domain": DOMAIN, "lang": LOCALE, "plural-forms": "nplurals=2; plural=(n != 1);"},
                **{msgid: [po_entries.get(msgid) or HU[msgid]] for msgid in sorted(js_strings | dynamic)},
            }
        },
    }
    json_path = LANG / f"{DOMAIN}-{LOCALE}-{EDITOR_HANDLE}.json"
    json_path.write_text(json.dumps(js_catalogue, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def decode_js_string(value: str) -> str:
    return bytes(value, "utf-8").decode("unicode_escape") if "\\" in value else value


def compile_mo(entries: dict[str, str], output: Path) -> None:
    pairs = sorted((msgid.encode("utf-8"), translated.encode("utf-8")) for msgid, translated in entries.items())
    count = len(pairs)
    original_table = 28
    translated_table = original_table + 8 * count
    original_data = translated_table + 8 * count
    originals = bytearray()
    translations = bytearray()
    original_offsets = []
    translation_offsets = []
    for original, _ in pairs:
        original_offsets.append((len(original), original_data + len(originals)))
        originals.extend(original + b"\0")
    translation_data = original_data + len(originals)
    for _, translated in pairs:
        translation_offsets.append((len(translated), translation_data + len(translations)))
        translations.extend(translated + b"\0")
    header = struct.pack("<7I", 0x950412DE, 0, count, original_table, translated_table, 0, 0)
    tables = b"".join(struct.pack("<2I", *entry) for entry in original_offsets)
    tables += b"".join(struct.pack("<2I", *entry) for entry in translation_offsets)
    output.write_bytes(header + tables + originals + translations)


if __name__ == "__main__":
    write_catalogues()
