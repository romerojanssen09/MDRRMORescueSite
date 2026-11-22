"""
AI Image-to-Text Router with RapidOCR & Token-Saving Policy
============================================================
Enforces machine-wide token-saving policy:
1. Always runs RapidOCR locally first (`local_ocr`).
2. Parallel OCR with ThreadPoolExecutor (thread-local RapidOCR instances).
3. If extracted text >= 15 readable characters: routes to text-only LLM payload.
4. Falls back to vision/multimodal payload only when < 15 characters or visual inspection explicitly requested.
5. Tracks answered captures to prevent redundant OCR and duplicate API submissions.
"""

from __future__ import annotations

import base64
import hashlib
import os
import sys
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple, Union

try:
    import local_ocr
    from local_ocr import extract_text_from_base64, extract_text_with_stats, preload_ocr
except ImportError:
    raise ImportError(
        "local_ocr is not installed or not in Python PATH. "
        "Ensure rapidocr and local_ocr are available."
    )

# Thread pool for lock-free parallel capture processing
_MAX_WORKERS = min(4, os.cpu_count() or 2)
_OCR_POOL = ThreadPoolExecutor(max_workers=_MAX_WORKERS, thread_name_prefix="RapidOCR")

# Minimum characters required to use text-only routing
MIN_TEXT_THRESHOLD = 15

# Cache for answered captures (hash -> text)
_ANSWERED_CAPTURES: set[str] = set()


def _compute_image_hash(image_input: Union[str, Path, bytes]) -> str:
    """Compute sha256 hash of an image file path or raw bytes."""
    if isinstance(image_input, (str, Path)):
        p = Path(image_input)
        if p.exists() and p.is_file():
            return hashlib.sha256(p.read_bytes()).hexdigest()
        return hashlib.sha256(str(image_input).encode("utf-8")).hexdigest()
    elif isinstance(image_input, bytes):
        return hashlib.sha256(image_input).hexdigest()
    return hashlib.sha256(repr(image_input).encode("utf-8")).hexdigest()


def route_image_to_llm(
    image_input: Union[str, Path, bytes],
    force_vision: bool = False,
    prompt_context: str = "",
) -> Dict[str, Any]:
    """
    Process an image capture and prepare the token-efficient LLM payload.

    Returns a dict with:
        - route: "text_llm" | "vision_llm" | "cached_skip"
        - payload: formatted prompt/data ready for LLM consumption
        - stats: OCR performance statistics (time, char count, line count)
        - extracted_text: raw OCR text if available
    """
    img_hash = _compute_image_hash(image_input)

    # Check if already processed/answered
    if img_hash in _ANSWERED_CAPTURES:
        return {
            "route": "cached_skip",
            "image_hash": img_hash,
            "message": "Capture was already processed and answered. Skipping.",
        }

    # Extract text locally via RapidOCR
    try:
        if isinstance(image_input, (str, Path)) and Path(image_input).exists():
            extracted_text, stats = extract_text_with_stats(image_input)
        elif isinstance(image_input, str) and (image_input.startswith("data:image") or len(image_input) > 200):
            extracted_text = extract_text_from_base64(image_input)
            stats = {"chars": len(extracted_text), "lines": len(extracted_text.splitlines())}
        else:
            extracted_text, stats = extract_text_with_stats(image_input)
    except Exception as e:
        extracted_text = ""
        stats = {"error": str(e), "chars": 0, "lines": 0}

    char_count = stats.get("chars", len(extracted_text.strip()))

    # Decision policy
    if not force_vision and char_count >= MIN_TEXT_THRESHOLD:
        # High text density -> route to text-only LLM
        _ANSWERED_CAPTURES.add(img_hash)
        payload = (
            f"{prompt_context}\n\n"
            f"[EXTRACTED IMAGE TEXT VIA LOCAL OCR (TOKEN-SAVER)]:\n"
            f"{extracted_text.strip()}"
        ).strip()

        return {
            "route": "text_llm",
            "image_hash": img_hash,
            "payload": payload,
            "extracted_text": extracted_text,
            "stats": stats,
            "token_saving": f"Routed {char_count} chars to text LLM instead of ~1600+ vision tokens.",
        }
    else:
        # Low text or forced visual inspection -> fall back to vision LLM
        reason = "Visual inspection forced" if force_vision else f"Low text density ({char_count} < {MIN_TEXT_THRESHOLD} chars)"
        _ANSWERED_CAPTURES.add(img_hash)
        return {
            "route": "vision_llm",
            "image_hash": img_hash,
            "reason": reason,
            "extracted_text": extracted_text,
            "stats": stats,
        }


def route_batch_captures(
    image_inputs: List[Union[str, Path, bytes]],
    force_vision: bool = False,
    prompt_context: str = "",
) -> List[Dict[str, Any]]:
    """
    Process multiple image captures concurrently using ThreadPoolExecutor.
    """
    def _worker(item: Union[str, Path, bytes]) -> Dict[str, Any]:
        return route_image_to_llm(item, force_vision=force_vision, prompt_context=prompt_context)

    return list(_OCR_POOL.map(_worker, image_inputs))


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python ai_image_router.py <image_path_1> [image_path_2 ...]")
        sys.exit(1)

    paths = sys.argv[1:]
    results = route_batch_captures(paths)
    for r in results:
        print("\n--- ROUTING RESULT ---")
        print(f"Route: {r['route']}")
        if "stats" in r:
            print(f"Stats: {r['stats']}")
        if "token_saving" in r:
            print(f"Notice: {r['token_saving']}")
        if r.get("extracted_text"):
            print("Preview:", r["extracted_text"][:120], "...")
