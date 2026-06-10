#!/usr/bin/env node
/**
 * Rasterizes the brand SVGs in assets-src/ into the WordPress.org-required PNGs
 * in luludesk-chat-memory/assets/.
 *
 * Outputs (exact WP.org dimensions):
 *   icon-128x128.png        — plugin directory icon (1x)
 *   icon-256x256.png        — plugin directory icon (retina)
 *   banner-772x250.png      — plugin header banner (1x)
 *   banner-1544x500.png     — plugin header banner (retina)
 *
 * `sharp` is not a dependency of this repo. We resolve it from the Luluclaw
 * workspace node_modules (the platform repo), since both repos live side by
 * side on this machine. If sharp cannot be loaded we exit non-zero WITHOUT
 * writing any PNGs, so we never ship a broken/half-rendered asset — the SVG
 * sources remain the source of truth in that case.
 *
 * Usage:  node scripts/build-assets.mjs
 */

import { readFileSync, mkdirSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import { createRequire } from "node:module";

const __dirname = dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = join(__dirname, "..");
const SRC_DIR = join(REPO_ROOT, "assets-src");
const OUT_DIR = join(REPO_ROOT, "luludesk-chat-memory", "assets");

// Resolve sharp from the sibling Luluclaw workspace.
const SHARP_PATHS = [
  "C:/Users/jrlop/OneDrive/Documents/GitHub/Luluclaw/node_modules/sharp",
  "C:/Users/jrlop/OneDrive/Documents/GitHub/Luluclaw/apps/web-next/node_modules/sharp",
];

async function loadSharp() {
  const require = createRequire(import.meta.url);
  for (const p of SHARP_PATHS) {
    try {
      // ESM-friendly import of a CJS module via file URL.
      const mod = await import(pathToFileURL(require.resolve(p)).href);
      return mod.default ?? mod;
    } catch {
      /* try next path */
    }
  }
  return null;
}

const JOBS = [
  { src: "icon.svg", out: "icon-128x128.png", w: 128, h: 128 },
  { src: "icon.svg", out: "icon-256x256.png", w: 256, h: 256 },
  { src: "banner.svg", out: "banner-772x250.png", w: 772, h: 250 },
  { src: "banner.svg", out: "banner-1544x500.png", w: 1544, h: 500 },
];

async function main() {
  const sharp = await loadSharp();
  if (!sharp) {
    console.error(
      "[build-assets] Could not load `sharp` from the Luluclaw workspace.\n" +
        "               SVG sources in assets-src/ are unchanged; no PNGs written.\n" +
        "               Install sharp or run from a machine where it resolves, then re-run.",
    );
    process.exit(1);
  }

  mkdirSync(OUT_DIR, { recursive: true });

  for (const job of JOBS) {
    const svg = readFileSync(join(SRC_DIR, job.src));
    await sharp(svg, { density: 384 })
      .resize(job.w, job.h, { fit: "fill" })
      .png({ compressionLevel: 9 })
      .toFile(join(OUT_DIR, job.out));
    console.log(`[build-assets] Wrote ${job.out} (${job.w}x${job.h})`);
  }

  console.log("[build-assets] Done.");
}

main().catch((err) => {
  console.error("[build-assets] Failed:", err);
  process.exit(1);
});
