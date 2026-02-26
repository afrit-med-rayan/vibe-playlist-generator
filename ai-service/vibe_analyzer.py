"""
vibe_analyzer.py
────────────────
Loads a BLIP image-captioning model, generates a caption for an uploaded image,
extracts keywords, and maps them to Spotify audio features via VIBE_MAP.
"""

from __future__ import annotations

import io
import logging
import re
from typing import Dict, List, Tuple

import nltk
from PIL import Image
from transformers import BlipForConditionalGeneration, BlipProcessor

logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Download NLTK data (runs once at startup)
# ---------------------------------------------------------------------------
for _pkg in ("punkt", "stopwords", "averaged_perceptron_tagger", "punkt_tab",
             "averaged_perceptron_tagger_eng"):
    try:
        nltk.download(_pkg, quiet=True)
    except Exception:
        pass

from nltk.corpus import stopwords  # noqa: E402 (after download)

STOPWORDS: set = set(stopwords.words("english"))

# ---------------------------------------------------------------------------
# VIBE_MAP — keyword → audio-feature deltas
# Each entry: (energy_delta, valence_delta, tempo_delta, acousticness_delta)
# ---------------------------------------------------------------------------
VIBE_MAP: Dict[str, Tuple[float, float, float, float]] = {
    # ── Energetic / intense ──────────────────────────────────────────────
    "vibrant":    ( 0.30,  0.25,  20.0, -0.20),
    "bright":     ( 0.20,  0.30,  15.0, -0.15),
    "intense":    ( 0.40,  0.00,  25.0, -0.25),
    "fire":       ( 0.40,  0.10,  20.0, -0.25),
    "explosive":  ( 0.45,  0.05,  25.0, -0.30),
    "dynamic":    ( 0.30,  0.15,  18.0, -0.20),
    "crowded":    ( 0.25,  0.10,  15.0, -0.15),
    "city":       ( 0.20,  0.10,  10.0, -0.10),
    "urban":      ( 0.20,  0.05,  10.0, -0.10),
    "street":     ( 0.15,  0.05,   8.0, -0.10),

    # ── Happy / joyful ───────────────────────────────────────────────────
    "sunny":      ( 0.25,  0.40,  15.0, -0.10),
    "colorful":   ( 0.20,  0.35,  12.0, -0.10),
    "joyful":     ( 0.25,  0.45,  15.0, -0.10),
    "fun":        ( 0.30,  0.40,  18.0, -0.15),
    "happy":      ( 0.25,  0.45,  15.0, -0.10),
    "smiling":    ( 0.15,  0.40,  10.0, -0.05),
    "laugh":      ( 0.20,  0.45,  12.0, -0.08),
    "celebration":( 0.35,  0.45,  20.0, -0.20),
    "party":      ( 0.40,  0.40,  22.0, -0.25),

    # ── Calm / peaceful ──────────────────────────────────────────────────
    "peaceful":   (-0.30,  0.15, -20.0,  0.35),
    "serene":     (-0.30,  0.15, -18.0,  0.35),
    "quiet":      (-0.35,  0.10, -22.0,  0.40),
    "calm":       (-0.30,  0.10, -20.0,  0.35),
    "tranquil":   (-0.30,  0.10, -20.0,  0.35),
    "still":      (-0.20,  0.05, -15.0,  0.30),
    "lake":       (-0.25,  0.15, -15.0,  0.30),
    "pond":       (-0.20,  0.10, -12.0,  0.25),
    "meadow":     (-0.20,  0.15, -12.0,  0.30),
    "countryside":(-0.20,  0.15, -10.0,  0.30),
    "field":      (-0.15,  0.10,  -8.0,  0.25),

    # ── Nature / organic ─────────────────────────────────────────────────
    "forest":     (-0.20,  0.10, -12.0,  0.30),
    "jungle":     ( 0.10, -0.05,  0.0,   0.15),
    "nature":     (-0.15,  0.15, -10.0,  0.30),
    "mountain":   (-0.10,  0.05,  0.0,   0.20),
    "ocean":      (-0.15,  0.10, -10.0,  0.25),
    "beach":      (-0.10,  0.20,  -5.0,  0.20),
    "sunset":     (-0.15,  0.20, -10.0,  0.25),
    "sunrise":    (-0.10,  0.25,  -5.0,  0.20),
    "sky":        (-0.10,  0.15,  -5.0,  0.15),
    "cloud":      (-0.15,  0.10, -10.0,  0.20),
    "snow":       (-0.20,  0.05, -15.0,  0.30),
    "winter":     (-0.15, -0.05, -10.0,  0.25),
    "autumn":     (-0.10, -0.10,  -8.0,  0.20),
    "rain":       (-0.15, -0.15, -10.0,  0.25),
    "fog":        (-0.20, -0.10, -12.0,  0.25),

    # ── Dark / melancholic ───────────────────────────────────────────────
    "dark":       (-0.15, -0.30, -10.0,  0.10),
    "night":      (-0.10, -0.20,  -8.0,  0.10),
    "stormy":     ( 0.20, -0.30,  10.0, -0.10),
    "moody":      (-0.10, -0.25, -10.0,  0.10),
    "gloomy":     (-0.15, -0.30, -12.0,  0.15),
    "shadow":     (-0.10, -0.20,  -8.0,  0.10),
    "lonely":     (-0.20, -0.25, -12.0,  0.15),
    "melancholy": (-0.20, -0.30, -15.0,  0.20),
    "sad":        (-0.20, -0.35, -15.0,  0.20),
    "crying":     (-0.25, -0.35, -18.0,  0.25),
    "abandoned":  (-0.15, -0.30, -10.0,  0.15),
    "empty":      (-0.20, -0.20, -12.0,  0.20),

    # ── Epic / dramatic ──────────────────────────────────────────────────
    "dramatic":   ( 0.40, -0.10,  15.0, -0.30),
    "majestic":   ( 0.35,  0.10,  10.0, -0.25),
    "vast":       ( 0.20,  0.05,   5.0, -0.10),
    "massive":    ( 0.35, -0.05,  12.0, -0.25),
    "grand":      ( 0.30,  0.05,  10.0, -0.20),
    "epic":       ( 0.40, -0.05,  15.0, -0.25),
    "powerful":   ( 0.35, -0.05,  12.0, -0.20),

    # ── Romantic / intimate ──────────────────────────────────────────────
    "warm":       (-0.10,  0.25,  -8.0,  0.20),
    "soft":       (-0.15,  0.20, -12.0,  0.25),
    "golden":     (-0.05,  0.25,  -5.0,  0.15),
    "intimate":   (-0.15,  0.20, -10.0,  0.25),
    "romantic":   (-0.10,  0.30, -10.0,  0.25),
    "couple":     (-0.05,  0.25,  -5.0,  0.15),
    "love":       (-0.05,  0.35,  -5.0,  0.15),
    "wedding":    (-0.05,  0.35, -10.0,  0.20),

    # ── Abstract / artistic ──────────────────────────────────────────────
    "abstract":   ( 0.05, -0.05,   0.0,  0.05),
    "artistic":   ( 0.05,  0.05,   0.0,  0.05),
    "colorful":   ( 0.15,  0.25,   8.0, -0.05),
    "minimalist": (-0.15, -0.05, -10.0,  0.15),
    "vintage":    (-0.10,  0.05,  -8.0,  0.20),
    "retro":      ( 0.05,  0.10,   5.0,  0.10),
}

# Default baseline values (neutral starting point)
_DEFAULTS = {"energy": 0.5, "valence": 0.5, "tempo": 120.0, "acousticness": 0.5}

# ---------------------------------------------------------------------------
# Model singleton (loaded once at startup)
# ---------------------------------------------------------------------------
_processor: BlipProcessor | None = None
_model: BlipForConditionalGeneration | None = None


def _load_model() -> None:
    global _processor, _model
    if _processor is None:
        logger.info("Loading BLIP model (first run may take a while)…")
        _processor = BlipProcessor.from_pretrained("Salesforce/blip-image-captioning-base")
        _model = BlipForConditionalGeneration.from_pretrained(
            "Salesforce/blip-image-captioning-base"
        )
        logger.info("BLIP model loaded ✓")


# ---------------------------------------------------------------------------
# Caption generation
# ---------------------------------------------------------------------------

def generate_caption(image_bytes: bytes) -> str:
    """Run BLIP captioning on raw image bytes and return caption string."""
    _load_model()
    image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    inputs = _processor(image, return_tensors="pt")  # type: ignore[arg-type]
    output = _model.generate(**inputs, max_new_tokens=50)  # type: ignore[union-attr]
    caption: str = _processor.decode(output[0], skip_special_tokens=True)  # type: ignore[union-attr]
    return caption.strip()


# ---------------------------------------------------------------------------
# Keyword extraction
# ---------------------------------------------------------------------------

def extract_keywords(caption: str) -> List[str]:
    """
    Tokenise caption, POS-tag it, and keep nouns (NN*) + adjectives (JJ*).
    Also perform a direct lookup against VIBE_MAP keys for exact matches.
    """
    # Clean and tokenize
    text = re.sub(r"[^a-zA-Z\s]", "", caption.lower())
    tokens = nltk.word_tokenize(text)

    # Remove stopwords
    tokens = [t for t in tokens if t not in STOPWORDS and len(t) > 2]

    # POS-tag and keep nouns + adjectives
    tagged = nltk.pos_tag(tokens)
    keywords: List[str] = [
        word for word, tag in tagged if tag.startswith(("NN", "JJ", "VB"))
    ]

    # Deduplicate while preserving order
    seen: set = set()
    unique_keywords: List[str] = []
    for kw in keywords:
        if kw not in seen:
            seen.add(kw)
            unique_keywords.append(kw)

    return unique_keywords[:15]  # cap at 15


# ---------------------------------------------------------------------------
# VIBE_MAP scoring
# ---------------------------------------------------------------------------

def score_vibe(keywords: List[str]) -> Dict[str, float]:
    """
    Accumulate VIBE_MAP deltas for each keyword, then clamp to valid ranges.
    Returns dict with energy, valence, tempo, acousticness.
    """
    energy      = _DEFAULTS["energy"]
    valence     = _DEFAULTS["valence"]
    tempo       = _DEFAULTS["tempo"]
    acousticness= _DEFAULTS["acousticness"]

    matches = 0
    for kw in keywords:
        if kw in VIBE_MAP:
            de, dv, dt, da = VIBE_MAP[kw]
            energy       += de
            valence      += dv
            tempo        += dt
            acousticness += da
            matches      += 1

    logger.debug("VIBE_MAP matched %d / %d keywords", matches, len(keywords))

    # Clamp
    energy       = max(0.05, min(0.99, energy))
    valence      = max(0.05, min(0.99, valence))
    tempo        = max(40.0, min(220.0, tempo))
    acousticness = max(0.05, min(0.99, acousticness))

    return {
        "energy":       round(energy, 3),
        "valence":      round(valence, 3),
        "tempo":        round(tempo, 1),
        "acousticness": round(acousticness, 3),
    }


# ---------------------------------------------------------------------------
# Main public API
# ---------------------------------------------------------------------------

def analyze_image(image_bytes: bytes) -> Dict:
    """
    Full pipeline: bytes → caption → keywords → vibe scores.
    Returns dict ready to be serialised as VibeResponse.
    """
    caption  = generate_caption(image_bytes)
    keywords = extract_keywords(caption)
    scores   = score_vibe(keywords)

    return {
        "caption":      caption,
        "keywords":     keywords,
        **scores,
    }
