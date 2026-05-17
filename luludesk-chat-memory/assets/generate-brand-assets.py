#!/usr/bin/env python3
"""
Generate wordpress.org marketing assets for LuluDesk plugin.
Brand spec from DESIGN.md: warm cream surface, pink #F2729B primary, deep rose #7A3E56 accent.

Run: python generate-brand-assets.py
Outputs into the current directory:
  - icon-128x128.png
  - icon-256x256.png
  - banner-772x250.png
  - banner-1544x500.png
"""
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import os

PINK = (242, 114, 155)
PINK_HOVER = (232, 96, 138)
DEEP_ROSE = (122, 62, 86)
CREAM = (255, 253, 249)
CREAM_ALT = (255, 252, 251)
TEXT_PRIMARY = (26, 26, 26)
TEXT_SECONDARY = (107, 107, 107)
BORDER = (240, 232, 228)
WHITE = (255, 255, 255)


def try_font(size, weight="bold"):
    candidates = [
        "C:/Windows/Fonts/segoeuib.ttf" if weight == "bold" else "C:/Windows/Fonts/segoeui.ttf",
        "C:/Windows/Fonts/arialbd.ttf" if weight == "bold" else "C:/Windows/Fonts/arial.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if weight == "bold" else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    ]
    for path in candidates:
        if os.path.exists(path):
            return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def rounded_rect(draw, xy, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(xy, radius=radius, fill=fill, outline=outline, width=width)


def draw_chat_bubble(img, center_x, center_y, size, primary, secondary):
    """Draw a chat bubble with a memory dot inside — the LuluDesk visual identity."""
    draw = ImageDraw.Draw(img, "RGBA")
    # Main bubble
    r = size // 2
    bubble_box = (center_x - r, center_y - r, center_x + r, center_y + r)
    rounded_rect(draw, bubble_box, radius=int(size * 0.28), fill=primary)
    # Tail
    tail_w = int(size * 0.18)
    tail_h = int(size * 0.22)
    tail_x = center_x - int(size * 0.12)
    tail_y = center_y + r - int(size * 0.04)
    draw.polygon([
        (tail_x, tail_y),
        (tail_x + tail_w, tail_y),
        (tail_x - int(size * 0.04), tail_y + tail_h),
    ], fill=primary)
    # Three dots = "memory ellipsis"
    dot_r = max(2, int(size * 0.06))
    spacing = int(size * 0.18)
    dot_y = center_y
    for i in (-1, 0, 1):
        dx = center_x + i * spacing
        draw.ellipse((dx - dot_r, dot_y - dot_r, dx + dot_r, dot_y + dot_r), fill=secondary)


def make_icon(size, out_path):
    img = Image.new("RGBA", (size, size), CREAM)
    # Subtle blush ring
    draw = ImageDraw.Draw(img, "RGBA")
    ring_inset = int(size * 0.06)
    rounded_rect(
        draw,
        (ring_inset, ring_inset, size - ring_inset, size - ring_inset),
        radius=int(size * 0.22),
        fill=CREAM_ALT,
        outline=BORDER,
        width=max(1, size // 128),
    )
    # Main mark
    bubble_size = int(size * 0.56)
    draw_chat_bubble(img, size // 2, int(size * 0.47), bubble_size, PINK, WHITE)
    img.save(out_path, "PNG", optimize=True)
    print(f"  -> {out_path} ({size}x{size})")


def make_banner(w, h, out_path, scale=1.0):
    img = Image.new("RGB", (w, h), CREAM)
    draw = ImageDraw.Draw(img, "RGBA")

    # Pink gradient wash on left side
    wash_w = int(w * 0.42)
    for x in range(wash_w):
        # Fade pink -> transparent left to right
        alpha = int(64 * (1 - x / wash_w))
        draw.line([(x, 0), (x, h)], fill=(*PINK, alpha))

    # Brand mark on the left
    mark_size = int(h * 0.56)
    mark_x = int(w * 0.10)
    mark_y = h // 2
    # Background card for mark
    pad = int(mark_size * 0.18)
    rounded_rect(
        draw,
        (mark_x - mark_size // 2 - pad, mark_y - mark_size // 2 - pad,
         mark_x + mark_size // 2 + pad, mark_y + mark_size // 2 + pad),
        radius=int(mark_size * 0.20),
        fill=WHITE,
        outline=BORDER,
        width=max(1, int(2 * scale)),
    )
    draw_chat_bubble(img, mark_x, mark_y - int(mark_size * 0.04), mark_size, PINK, WHITE)

    # Wordmark + tagline
    title_size = int(h * 0.22)
    tag_size = int(h * 0.10)
    title_font = try_font(title_size, "bold")
    tag_font = try_font(tag_size, "regular")

    text_x = int(w * 0.28)
    title_y = int(h * 0.30)
    tag_y = int(h * 0.58)

    draw.text((text_x, title_y), "LuluDesk", fill=TEXT_PRIMARY, font=title_font)
    draw.text((text_x, tag_y), "AI Chat with Memory for WordPress", fill=DEEP_ROSE, font=tag_font)

    # Small badge: "Persistent memory · Zero config"
    badge_y = tag_y + int(tag_size * 1.6)
    badge_font = try_font(max(10, int(tag_size * 0.72)), "bold")
    badge_text = "PERSISTENT MEMORY  •  ZERO CONFIG"
    bbox = draw.textbbox((0, 0), badge_text, font=badge_font)
    bw, bh = bbox[2] - bbox[0], bbox[3] - bbox[1]
    pad_x, pad_y = int(bh * 0.6), int(bh * 0.35)
    rounded_rect(
        draw,
        (text_x, badge_y, text_x + bw + pad_x * 2, badge_y + bh + pad_y * 2),
        radius=int((bh + pad_y * 2) / 2),
        fill=(255, 240, 243),
        outline=(247, 212, 223),
        width=max(1, int(1 * scale)),
    )
    draw.text((text_x + pad_x, badge_y + pad_y - int(bh * 0.15)),
              badge_text, fill=DEEP_ROSE, font=badge_font)

    img.save(out_path, "PNG", optimize=True)
    print(f"  -> {out_path} ({w}x{h})")


def main():
    base = os.path.dirname(os.path.abspath(__file__))
    os.chdir(base)
    print("Generating LuluDesk wp.org assets...")
    make_icon(128, "icon-128x128.png")
    make_icon(256, "icon-256x256.png")
    make_banner(772, 250, "banner-772x250.png", scale=1.0)
    make_banner(1544, 500, "banner-1544x500.png", scale=2.0)
    print("Done. 4 brand assets generated.")
    print("Screenshots (screenshot-1..4.png) still need REAL captures from a live WP install.")


if __name__ == "__main__":
    main()
