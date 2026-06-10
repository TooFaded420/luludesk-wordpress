#!/usr/bin/env node
/**
 * Builds dist/luludesk-chat-memory.zip — the distributable WordPress plugin zip.
 *
 * WordPress expects the plugin folder at the TOP LEVEL of the zip, i.e. the
 * archive must contain `luludesk-chat-memory/luludesk-chat-memory.php`, not the
 * files at the root. We guarantee this by staging the plugin into a temp dir
 * under a `luludesk-chat-memory/` folder, then zipping that folder.
 *
 * Excluded from the shipped zip:
 *   - *.png.txt and *.txt asset placeholders (real PNGs ship; placeholders do not)
 *   - the repo .github/ directory (CI config, not plugin code)
 *   - any dist/ directory
 *   - common junk: .DS_Store, Thumbs.db, *.swp, node_modules
 *
 * Zip mechanism: no third-party deps. On Windows we shell out to PowerShell's
 * Compress-Archive; on macOS/Linux we use the `zip` CLI. Either way the staged
 * directory is the single input so rooting is correct on every platform.
 *
 * Usage:  node scripts/build-zip.mjs
 */

import { execFileSync } from "node:child_process";
import {
  cpSync,
  existsSync,
  mkdirSync,
  mkdtempSync,
  rmSync,
  statSync,
} from "node:fs";
import { tmpdir } from "node:os";
import { join, dirname, basename } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = join(__dirname, "..");
const PLUGIN_SLUG = "luludesk-chat-memory";
const SRC_DIR = join(REPO_ROOT, PLUGIN_SLUG);
const DIST_DIR = join(REPO_ROOT, "dist");
const OUT_ZIP = join(DIST_DIR, `${PLUGIN_SLUG}.zip`);

// Paths inside the plugin that must NEVER ship.
const EXCLUDE_NAMES = new Set([
  ".DS_Store",
  "Thumbs.db",
  "desktop.ini",
  "node_modules",
  "dist",
  ".github",
]);

/** Returns true if a given source path should be copied into the staged plugin. */
function filter(src) {
  const name = basename(src);
  if (EXCLUDE_NAMES.has(name)) return false;
  // Drop the .txt asset placeholders (e.g. banner-772x250.png.txt, screenshot-*.txt).
  if (name.endsWith(".txt") && name !== "readme.txt") return false;
  return true;
}

function main() {
  if (!existsSync(SRC_DIR)) {
    console.error(`[build-zip] Plugin source not found: ${SRC_DIR}`);
    process.exit(1);
  }

  // Fresh dist/.
  mkdirSync(DIST_DIR, { recursive: true });
  if (existsSync(OUT_ZIP)) rmSync(OUT_ZIP);

  // Stage into <temp>/luludesk-chat-memory so the zip roots at that folder.
  const stageRoot = mkdtempSync(join(tmpdir(), "luludesk-build-"));
  const stagePlugin = join(stageRoot, PLUGIN_SLUG);

  try {
    cpSync(SRC_DIR, stagePlugin, { recursive: true, filter });

    // Zip the staged folder. Input is the folder itself → correct top-level rooting.
    if (process.platform === "win32") {
      // Compress-Archive: -Path the folder, -DestinationPath the zip.
      execFileSync(
        "powershell",
        [
          "-NoProfile",
          "-NonInteractive",
          "-Command",
          `Compress-Archive -Path '${stagePlugin}' -DestinationPath '${OUT_ZIP}' -Force`,
        ],
        { stdio: "inherit" },
      );
    } else {
      // zip -r out.zip luludesk-chat-memory  (run from stageRoot for clean paths).
      execFileSync("zip", ["-r", "-q", OUT_ZIP, PLUGIN_SLUG], {
        cwd: stageRoot,
        stdio: "inherit",
      });
    }
  } finally {
    rmSync(stageRoot, { recursive: true, force: true });
  }

  const size = statSync(OUT_ZIP).size;
  const kb = (size / 1024).toFixed(1);
  console.log(`[build-zip] Wrote ${OUT_ZIP} (${size} bytes / ${kb} KB)`);
  console.log(`[build-zip] Top-level folder in archive: ${PLUGIN_SLUG}/`);
}

main();
