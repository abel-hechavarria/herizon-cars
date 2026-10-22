"""Merge HCars Process strings into the existing Hungarian catalogues."""
from pathlib import Path
import importlib.util
import re

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('button_i18n', ROOT / 'scripts/button-translations.py')
i18n = importlib.util.module_from_spec(spec)
spec.loader.exec_module(i18n)
HU = {
    'Background image appearance': 'Háttérkép megjelenése',
    'Background image position': 'Háttérkép helyzete',
    'Background image width': 'Háttérkép szélessége',
    'Bottom': 'Alul', 'Button URL': 'Gomb hivatkozása', 'Button variant': 'Gombváltozat',
    'Card background': 'Kártya háttere', 'Card background color': 'Kártya háttérszíne',
    'Card columns': 'Kártyaoszlopok', 'Card content padding': 'Kártya belső térköze',
    'Card corner radius': 'Kártyasarkok lekerekítése', 'Card minimum height': 'Kártya minimális magassága',
    'Card overlay': 'Kártya fedőrétege', 'Card spacing': 'Kártyák közötti térköz',
    'Content and cards layout': 'Tartalom és kártyák elrendezése', 'Content to cards spacing': 'Tartalom és kártyák közötti térköz',
    'Copy maximum width': 'Szöveg maximális szélessége', 'Copy width': 'Szövegoszlop szélessége',
    'Description': 'Leírás', 'Description color': 'Leírás színe', 'Diagonal cut': 'Átlós vágás',
    'Eyebrow': 'Felső címke', 'HCars Process': 'HCars Process', 'Image alternative text': 'Kép helyettesítő szövege',
    'Image overlay': 'Kép fedőrétege', 'Image position': 'Kép helyzete', 'Inherit': 'Öröklés',
    'Minimum height': 'Minimális magasság', 'Number (blank uses order)': 'Sorszám (üresen automatikus)',
    'Number color': 'Sorszám színe', 'Open card link in new tab': 'Kártyahivatkozás megnyitása új lapon',
    'Optional card URL': 'Kártya hivatkozása (opcionális)', 'Overlay color': 'Fedőréteg színe',
    'Section background and cards': 'Szakasz háttere és kártyák', 'Section background color': 'Szakasz háttérszíne',
    'Section background image': 'Szakasz háttérképe', 'Section horizontal padding': 'Szakasz vízszintes belső térköze',
    'Section vertical padding': 'Szakasz függőleges belső térköze', 'Show button': 'Gomb megjelenítése',
    'Side by side': 'Egymás mellett', 'Stacked': 'Egymás alatt', 'Step description': 'Lépés leírása',
    'Step number': 'Lépés sorszáma', 'Step title': 'Lépés címe', 'Steps': 'Lépések',
    'Title': 'Cím', 'Title HTML tag': 'Cím HTML-címkéje', 'Title color': 'Cím színe', 'Top': 'Felül', 'color': 'színe',
}


def generate():
    source = (ROOT / 'inc/widgets/class-widget-process.php').read_text(encoding='utf-8')
    found = re.findall(r"(?:__|esc_html__|esc_attr__)\(\s*(['\"])(.*?)\1\s*,\s*['\"]herizon-cars['\"]", source)
    strings = {value.replace('\\n', '\n') if quote == '"' else value for quote, value in found}
    po_path = ROOT / 'languages/hu_HU.po'
    pot_path = ROOT / 'languages/herizon-cars.pot'
    blocks, entries = i18n.parse_po(po_path)
    pot_blocks, pot_entries = i18n.parse_po(pot_path)
    for value in sorted(strings):
        if value not in entries:
            # Hungarian default content remains Hungarian; interface labels need explicit translations.
            translated = HU.get(value)
            if translated is None and any(ord(char) > 127 for char in value):
                translated = value
            if translated is None:
                raise ValueError('Missing Hungarian translation: ' + value)
            blocks.append(i18n.po_line('msgid', value) + '\n' + i18n.po_line('msgstr', translated))
            entries[value] = translated
        if value not in pot_entries:
            pot_blocks.append('# HCars Process\n' + i18n.po_line('msgid', value) + '\nmsgstr ""')
    po_path.write_text('\n\n'.join(blocks).rstrip() + '\n', encoding='utf-8', newline='\n')
    pot_path.write_text('\n\n'.join(pot_blocks).rstrip() + '\n', encoding='utf-8', newline='\n')
    i18n.compile_mo(entries, ROOT / 'languages/hu_HU.mo')
    print('HCars Process Hungarian catalogues PASS: ' + str(len(strings)) + ' strings')


if __name__ == '__main__':
    generate()
