#!/bin/bash
# ============================================================================
# SCRIPT DOWNLOAD IMMAGINI PRODOTTI RETRO GAMING
# ============================================================================
# Scarica immagini reali da Wikimedia Commons e altre fonti pubbliche
# Licenze: Public Domain / Creative Commons

cd "$(dirname "$0")/html/images/products" || exit 1

echo "📥 Download immagini prodotti vintage..."

# Commodore 64 (Wikimedia Commons)
echo "⬇️  Commodore 64..."
wget -q -O c64.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/Commodore-64-Computer-FL.jpg/800px-Commodore-64-Computer-FL.jpg" 2>/dev/null || \
curl -s -o c64.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/Commodore-64-Computer-FL.jpg/800px-Commodore-64-Computer-FL.jpg"

# Commodore Amiga 500
echo "⬇️  Amiga 500..."
wget -q -O amiga500.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/Amiga500_system.jpg/800px-Amiga500_system.jpg" 2>/dev/null || \
curl -s -o amiga500.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/Amiga500_system.jpg/800px-Amiga500_system.jpg"

# ZX Spectrum
echo "⬇️  ZX Spectrum..."
wget -q -O spectrum.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Sinclair_ZX_Spectrum%2B.jpg/800px-Sinclair_ZX_Spectrum%2B.jpg" 2>/dev/null || \
curl -s -o spectrum.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Sinclair_ZX_Spectrum%2B.jpg/800px-Sinclair_ZX_Spectrum%2B.jpg"

# Nintendo NES
echo "⬇️  Nintendo NES..."
wget -q -O nes.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/8/82/NES-Console-Set.jpg/800px-NES-Console-Set.jpg" 2>/dev/null || \
curl -s -o nes.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/8/82/NES-Console-Set.jpg/800px-NES-Console-Set.jpg"

# Sega Master System
echo "⬇️  Sega Master System..."
wget -q -O mastersystem.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/6/6a/Sega-Master-System-Set.jpg/800px-Sega-Master-System-Set.jpg" 2>/dev/null || \
curl -s -o mastersystem.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/6/6a/Sega-Master-System-Set.jpg/800px-Sega-Master-System-Set.jpg"

# Joystick Competition Pro
echo "⬇️  Joystick Competition Pro..."
wget -q -O joystick.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Competition_Pro_Joystick.jpg/800px-Competition_Pro_Joystick.jpg" 2>/dev/null || \
curl -s -o joystick.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Competition_Pro_Joystick.jpg/800px-Competition_Pro_Joystick.jpg"

# Floppy Disk 5.25"
echo "⬇️  Floppy Disk..."
wget -q -O floppy.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Floppy_disk_2009_G1.jpg/800px-Floppy_disk_2009_G1.jpg" 2>/dev/null || \
curl -s -o floppy.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Floppy_disk_2009_G1.jpg/800px-Floppy_disk_2009_G1.jpg"

# The Last Ninja (placeholder - difficile trovare box art libero)
echo "⬇️  Last Ninja (placeholder)..."
# Usa l'immagine placeholder già creata

echo ""
echo "✅ Download completato!"
echo "📁 Immagini salvate in: $(pwd)"
echo ""
ls -lh *.jpg | awk '{print "  ", $9, "-", $5}'
echo ""
echo "ℹ️  Fonte: Wikimedia Commons (Public Domain / CC-BY-SA)"
