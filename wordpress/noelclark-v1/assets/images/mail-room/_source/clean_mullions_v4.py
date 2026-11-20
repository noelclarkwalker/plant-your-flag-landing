#!/usr/bin/env python3
"""Localized mullion cleanup: opaque black frames, clear panes, preserve originals.
Reads architecture-original/*, writes ../architecture/*. Does not regenerate architecture.
Requires: pillow, numpy, scipy.
"""
from pathlib import Path
from PIL import Image, ImageFilter
import numpy as np
from scipy import ndimage

SRC = Path(__file__).resolve().parent / "architecture-original"
OUT = Path(__file__).resolve().parent.parent / "architecture"
METAL = np.array([18, 16, 14], dtype=np.float32)

def neutralize_lighting_brick_sill(arr):
    rgb = arr[:,:,:3].astype(np.float32)
    a = arr[:,:,3].astype(np.float32)
    R,G,B = rgb[:,:,0], rgb[:,:,1], rgb[:,:,2]
    L = 0.2126*R + 0.7152*G + 0.0722*B
    out = rgb.copy()
    alpha_w = a/255.0
    metalish = (a > 200) & (L < 40) & (((rgb.max(2)-rgb.min(2))/(rgb.max(2)+1e-3)) < 0.22)
    highlight = np.clip((L - 85.0) / 100.0, 0.0, 1.0) * alpha_w
    for c in range(3):
        ch = out[:,:,c]
        out[:,:,c] = ch - highlight * (ch - 68.0) * 0.55
    R2,G2,B2 = out[:,:,0], out[:,:,1], out[:,:,2]
    L2 = 0.2126*R2 + 0.7152*G2 + 0.0722*B2
    warm = np.clip((R2 - B2 - 18.0) / 55.0, 0.0, 1.0) * np.clip((L2 - 90.0)/70.0, 0.0, 1.0) * alpha_w
    out[:,:,0] -= warm * 18.0
    out[:,:,1] -= warm * 5.0
    out[:,:,2] += warm * 10.0
    h = a.shape[0]
    lower = np.zeros_like(L2, dtype=bool); lower[int(h*0.58):,:] = True
    sill_hot = lower & (L2 > 110) & (a >= 180) & (~metalish)
    for c in range(3):
        out[:,:,c] = np.where(sill_hot, out[:,:,c]*0.78 + 55.0*0.22, out[:,:,c])
    out = np.clip(out, 0, 255)
    result = arr.copy().astype(np.float32)
    apply = (a > 0) & (~metalish)
    for c in range(3):
        result[:,:,c] = np.where(apply, out[:,:,c], arr[:,:,c])
    result[:,:,3] = a
    return result.astype(np.uint8)

def clean_frames(arr, mobile=False):
    a0 = arr[:,:,3].astype(np.float32)
    rgb0 = arr[:,:,:3].astype(np.float32)
    h,w = a0.shape
    if mobile:
        x0, y_sill_start = 360, int(h * 0.62)
    else:
        x0, y_sill_start = 548, 630
    xs = np.broadcast_to(np.arange(w),(h,w))
    ys = np.broadcast_to(np.arange(h)[:,None],(h,w))
    in_win = (xs >= x0) & (ys < y_sill_start)
    L = 0.2126*rgb0[:,:,0]+0.7152*rgb0[:,:,1]+0.0722*rgb0[:,:,2]
    pane = (a0 < 40) & in_win
    pane_dil = np.array(Image.fromarray((pane.astype(np.uint8)*255)).filter(ImageFilter.MaxFilter(3))) > 127
    labeled, n = ndimage.label(pane_dil)
    keep_pane = np.zeros_like(pane, dtype=bool)
    for i in range(1, n+1):
        m = labeled == i
        if (m & pane).sum() > 500:
            keep_pane |= m
    halo = in_win & (a0 < 180) & (L > 180) & (~keep_pane)
    keep_pane = keep_pane | (halo & (np.array(Image.fromarray((keep_pane.astype(np.uint8)*255)).filter(ImageFilter.MaxFilter(3))) > 127))
    frame = in_win & (~keep_pane)
    out_rgb = rgb0.copy(); out_a = a0.copy()
    out_a = np.where(keep_pane, 0.0, out_a)
    for c in range(3):
        out_rgb[:,:,c] = np.where(frame, METAL[c], out_rgb[:,:,c])
    out_a = np.where(frame, 255.0, out_a)
    mid = in_win & (out_a > 0) & (out_a < 255)
    out_a = np.where(mid, 255.0, out_a)
    for c in range(3):
        out_rgb[:,:,c] = np.where(mid, METAL[c], out_rgb[:,:,c])
    return np.dstack([out_rgb, out_a]).astype(np.uint8)

def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for name, mobile in [("desktop-mask.webp", False), ("desktop-mask.png", False), ("mobile-mask.webp", True), ("mobile-mask.png", True)]:
        orig = np.array(Image.open(SRC/name).convert("RGBA"))
        final = neutralize_lighting_brick_sill(clean_frames(orig, mobile=mobile))
        im = Image.fromarray(final, "RGBA")
        if name.endswith(".webp"):
            im.save(OUT/name, "WEBP", quality=95, method=6)
        else:
            im.save(OUT/name, "PNG", optimize=True)
        print("wrote", OUT/name)

if __name__ == "__main__":
    main()
