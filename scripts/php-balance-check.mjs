// Lightweight PHP brace/paren/bracket balance sanity check (NOT a real linter).
// Skips strings, // and # line comments, and block comments.
import { readFileSync } from "node:fs";
import { join } from "node:path";

const root = join(
  "C:/Users/jrlop/OneDrive/Documents/GitHub/luludesk-wordpress/luludesk-chat-memory",
);
const files = [
  "luludesk-chat-memory.php",
  "uninstall.php",
  "includes/class-luludesk-plugin.php",
  "includes/class-luludesk-settings.php",
  "includes/class-luludesk-injector.php",
  "includes/class-luludesk-telemetry.php",
  "includes/class-luludesk-webhook.php",
  "includes/class-luludesk-kb-settings.php",
];

const SQ = String.fromCharCode(39); // '
const DQ = String.fromCharCode(34); // "
const BS = String.fromCharCode(92); // backslash

let anyBad = false;
for (const f of files) {
  const s = readFileSync(join(root, f), "utf8");
  let k = 0, p = 0, b = 0;
  let inSQ = false, inDQ = false, inBlock = false, inLine = false;
  for (let i = 0; i < s.length; i++) {
    const c = s[i], n = s[i + 1];
    if (inLine) { if (c === "\n") inLine = false; continue; }
    if (inBlock) { if (c === "*" && n === "/") { inBlock = false; i++; } continue; }
    if (inSQ) { if (c === BS) { i++; continue; } if (c === SQ) inSQ = false; continue; }
    if (inDQ) { if (c === BS) { i++; continue; } if (c === DQ) inDQ = false; continue; }
    if (c === "/" && n === "/") { inLine = true; continue; }
    if (c === "#") { inLine = true; continue; }
    if (c === "/" && n === "*") { inBlock = true; i++; continue; }
    if (c === SQ) { inSQ = true; continue; }
    if (c === DQ) { inDQ = true; continue; }
    if (c === "{") k++; else if (c === "}") k--;
    else if (c === "(") p++; else if (c === ")") p--;
    else if (c === "[") b++; else if (c === "]") b--;
  }
  const ok = k === 0 && p === 0 && b === 0;
  if (!ok) anyBad = true;
  console.log(
    f.padEnd(46),
    `braces=${k} parens=${p} brackets=${b}`,
    ok ? "OK" : "*** UNBALANCED ***",
  );
}
process.exit(anyBad ? 1 : 0);
