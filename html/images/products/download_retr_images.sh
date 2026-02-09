#!/usr/bin/env bash
set -euo pipefail

OUT_DIR="retro_images_500"
ZIP_NAME="retro_images_500.zip"

mkdir -p "$OUT_DIR"

# Check deps
for cmd in curl jq magick convert zip; do
  if ! command -v "$cmd" >/dev/null 2>&1; then
    echo "Errore: comando '$cmd' non trovato. Installa le dipendenze (curl jq ImageMagick zip)."
    exit 1
  fi
done

# Wikimedia Commons API endpoint
API="https://commons.wikimedia.org/w/api.php"

# Product list (one per line)
PRODUCTS=(
"Commodore 64"
"Commodore Amiga 500"
"ZX Spectrum 48K"
"Apple II Europlus"
"Atari 800XL"
"MSX Sony HitBit HB-75P"
"Amstrad CPC 464"
"Nintendo NES"
"Sega Master System"
"Atari 2600 Jr"
"Datasette C1530"
"Floppy Drive 1541-II"
"Monitor 1084S"
"Joystick Competition Pro"
"Floppy Disk 5.25\" DD x10"
"Cassette C30 x5"
"Epyx Fast Load C64"
"Compute! Gazette 1985"
"The Last Ninja C64"
"Elite C64/Spectrum"
"Zak McKracken C64"
"Maniac Mansion C64"
"Impossible Mission C64"
"Turrican C64"
"International Karate C64"
"Monkey Island Amiga"
"Lemmings Amiga"
"Speedball 2 Amiga"
"Shadow of the Beast Amiga"
"Sensible Soccer Amiga"
)

slugify () {
  # lowercase, replace non-alnum with underscores, trim underscores
  echo "$1" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/_/g; s/^_+|_+$//g'
}

download_one () {
  local query="$1"
  local base
  base="$(slugify "$query")"
  local out_jpg="${OUT_DIR}/${base}.jpg"
  local tmp="${OUT_DIR}/${base}.tmp"
  local json="${OUT_DIR}/${base}.json"

  if [[ -f "$out_jpg" ]]; then
    echo "OK (già presente): $out_jpg"
    return 0
  fi

  echo "Cerco su Commons: $query"

  # Search for file pages on Commons (namespace 6 = File:)
  # We ask for imageinfo (direct url) and take the first result.
  curl -sG "$API" \
    --data-urlencode "action=query" \
    --data-urlencode "format=json" \
    --data-urlencode "generator=search" \
    --data-urlencode "gsrsearch=${query} filetype:bitmap" \
    --data-urlencode "gsrlimit=1" \
    --data-urlencode "gsrnamespace=6" \
    --data-urlencode "prop=imageinfo" \
    --data-urlencode "iiprop=url" \
    --data-urlencode "iiurlwidth=2000" \
    > "$json"

  # Extract the image URL (prefer the scaled url if present; fallback to original)
  local url
  url="$(jq -r '
    .query.pages
    | to_entries[0].value.imageinfo[0]
    | (.thumburl // .url // empty)
  ' "$json")"

  if [[ -z "$url" || "$url" == "null" ]]; then
    echo "  !! Nessuna immagine trovata per: $query"
    rm -f "$json"
    return 1
  fi

  echo "  Scarico: $url"
  curl -L -sS "$url" -o "$tmp"

  # Convert/crop to square 500x500 JPG, center-crop without distortion
  # Using ImageMagick: resize to cover then crop
  # Some distros use 'magick'; others use 'convert'. We'll try magick first.
  if command -v magick >/dev/null 2>&1; then
    magick "$tmp" -auto-orient -resize "500x500^" -gravity center -extent 500x500 -quality 88 "$out_jpg"
  else
    convert "$tmp" -auto-orient -resize "500x500^" -gravity center -extent 500x500 -quality 88 "$out_jpg"
  fi

  rm -f "$tmp" "$json"
  echo "  ✅ Salvato: $out_jpg"
}

failures=0
for p in "${PRODUCTS[@]}"; do
  if ! download_one "$p"; then
    failures=$((failures+1))
  fi
done

echo
echo "Completato. Immagini in: $OUT_DIR"
echo "Fallimenti: $failures"

# Create zip
rm -f "$ZIP_NAME"
zip -q -r "$ZIP_NAME" "$OUT_DIR"
echo "ZIP creato: $ZIP_NAME"

