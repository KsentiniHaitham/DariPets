/**
 * Génère les icônes PNG pour le manifest PWA.
 * Usage : node generate-icons.mjs
 * Aucune dépendance externe — utilise uniquement node:zlib et node:fs.
 */
import { deflateSync } from 'node:zlib'
import { writeFileSync, mkdirSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dir = dirname(fileURLToPath(import.meta.url))
const outDir = join(__dir, 'public', 'icons')
mkdirSync(outDir, { recursive: true })

// Couleurs DariPets
const PRIMARY = { r: 0x7c, g: 0x3a, b: 0xed }   // #7C3AED violet principal
const WHITE   = { r: 0xff, g: 0xff, b: 0xff }

// ── helpers PNG ────────────────────────────────────────────────────────────
function crc32(buf) {
  const table = crc32.table ??= (() => {
    const t = new Uint32Array(256)
    for (let i = 0; i < 256; i++) {
      let c = i
      for (let k = 0; k < 8; k++) c = (c & 1) ? 0xedb88320 ^ (c >>> 1) : c >>> 1
      t[i] = c
    }
    return t
  })()
  let crc = 0xffffffff
  for (const b of buf) crc = table[(crc ^ b) & 0xff] ^ (crc >>> 8)
  return (crc ^ 0xffffffff) >>> 0
}

function chunk(type, data) {
  const typeBytes = Buffer.from(type, 'ascii')
  const len = Buffer.alloc(4); len.writeUInt32BE(data.length)
  const body = Buffer.concat([typeBytes, data])
  const crcBuf = Buffer.alloc(4); crcBuf.writeUInt32BE(crc32(body))
  return Buffer.concat([len, body, crcBuf])
}

function makePng(pixels, size) {
  const sig = Buffer.from('\x89PNG\r\n\x1a\n', 'binary')

  // IHDR
  const ihdr = Buffer.alloc(13)
  ihdr.writeUInt32BE(size, 0)   // width
  ihdr.writeUInt32BE(size, 4)   // height
  ihdr[8]  = 8   // bit depth
  ihdr[9]  = 2   // color type RGB
  ihdr[10] = 0; ihdr[11] = 0; ihdr[12] = 0

  // IDAT: filter byte 0 + RGB per pixel, per scanline
  const raw = Buffer.alloc(size * (1 + size * 3))
  for (let y = 0; y < size; y++) {
    raw[y * (1 + size * 3)] = 0 // filter None
    for (let x = 0; x < size; x++) {
      const { r, g, b } = pixels[y * size + x]
      const off = y * (1 + size * 3) + 1 + x * 3
      raw[off] = r; raw[off + 1] = g; raw[off + 2] = b
    }
  }

  return Buffer.concat([
    sig,
    chunk('IHDR', ihdr),
    chunk('IDAT', deflateSync(raw, { level: 9 })),
    chunk('IEND', Buffer.alloc(0)),
  ])
}

// ── dessin patte ──────────────────────────────────────────────────────────
function circle(cx, cy, rx, ry = rx) {
  return (x, y) => ((x - cx) ** 2) / (rx ** 2) + ((y - cy) ** 2) / (ry ** 2) <= 1
}

function drawIcon(size, bg, fg, maskable = false) {
  const pixels = new Array(size * size)

  // fond
  pixels.fill(bg)

  // padding safe zone pour maskable (10% de chaque côté)
  const pad  = maskable ? Math.round(size * 0.12) : Math.round(size * 0.06)
  const draw = size - pad * 2

  // ── patte DariPets ────────────────────────────────────────────────────
  // Paume centrale (ellipse)
  const palmCX = size / 2
  const palmCY = size / 2 + draw * 0.08
  const palmRX = draw * 0.22
  const palmRY = draw * 0.17
  const inPalm = circle(palmCX, palmCY, palmRX, palmRY)

  // 4 coussinets : haut-gauche, haut, haut-droit, droite
  const pads = [
    circle(palmCX - draw * 0.21, palmCY - draw * 0.24, draw * 0.09, draw * 0.10),
    circle(palmCX,                palmCY - draw * 0.31, draw * 0.09, draw * 0.10),
    circle(palmCX + draw * 0.21, palmCY - draw * 0.24, draw * 0.09, draw * 0.10),
    circle(palmCX - draw * 0.33, palmCY - draw * 0.10, draw * 0.075, draw * 0.085),
  ]

  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      const i = y * size + x
      if (inPalm(x, y) || pads.some(p => p(x, y))) {
        pixels[i] = fg
      }
    }
  }

  return pixels
}

// ── génération ────────────────────────────────────────────────────────────
const configs = [
  { name: 'icon-192.png',          size: 192,  maskable: false },
  { name: 'icon-512.png',          size: 512,  maskable: false },
  { name: 'icon-maskable-192.png', size: 192,  maskable: true  },
  { name: 'icon-maskable-512.png', size: 512,  maskable: true  },
]

for (const { name, size, maskable } of configs) {
  const pixels = drawIcon(size, PRIMARY, WHITE, maskable)
  const png    = makePng(pixels, size)
  const path   = join(outDir, name)
  writeFileSync(path, png)
  console.log(`✓ ${name}  (${png.length} bytes)`)
}

console.log('\nIcones générées dans public/icons/ — committer et pusher.')
