# Plugin Directory Assets

WordPress.org reads banner/icon/screenshot assets from the **`/assets`** directory
in SVN (the plugin's `assets/` folder at the repo root of the SVN checkout), NOT
from inside the plugin zip. When publishing, copy the PNGs below into the SVN
`assets/` directory.

## Status

| Asset | File | Status |
|-------|------|--------|
| Icon 1x | `icon-128x128.png` | ✅ Real branded PNG (generated from `assets-src/icon.svg`) |
| Icon retina | `icon-256x256.png` | ✅ Real branded PNG |
| Banner 1x | `banner-772x250.png` | ✅ Real branded PNG (from `assets-src/banner.svg`) |
| Banner retina | `banner-1544x500.png` | ✅ Real branded PNG |
| Screenshot 1 | `screenshot-1.png` | ⏳ TODO — must be a REAL capture |
| Screenshot 2 | `screenshot-2.png` | ⏳ TODO — must be a REAL capture |
| Screenshot 3 | `screenshot-3.png` | ⏳ TODO — must be a REAL capture |
| Screenshot 4 | `screenshot-4.png` | ⏳ TODO — must be a REAL capture |

## Regenerating the icon + banner

The PNGs are rasterized from the brand SVGs in `../assets-src/` (warm pink
`#F2729B`, cream `#FFFDF9`, deep rose `#7A3E56`, per the LuluClaw DESIGN.md). To
regenerate after editing an SVG:

```
node scripts/build-assets.mjs
```

This resolves `sharp` from the sibling Luluclaw workspace `node_modules` and
writes all four PNGs at their exact WordPress.org dimensions.

## Screenshots — still TODO (honest note)

The four screenshots are **placeholders** and MUST be replaced with genuine
captures of this plugin running in a real `wp-admin`. They cannot be mocked or
generated — WordPress.org reviewers expect screenshots that match the live UI.

Capture these against a real WordPress install with the plugin activated:

1. `screenshot-1.png` — Settings → LuluDesk (Widget tab): token field + Test Connection.
2. `screenshot-2.png` — Green "Connected — widget script reachable." confirmation.
3. `screenshot-3.png` — The chat launcher button on a live front-end page.
4. `screenshot-4.png` — Chat widget open, showing a returning-visitor memory conversation.

The descriptive captions for these live in `readme.txt` under `== Screenshots ==`.
Recommended size: 1280×800 (or 2× for retina). PNG.
