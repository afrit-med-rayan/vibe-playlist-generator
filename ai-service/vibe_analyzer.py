"""
vibe_analyzer.py
────────────────
Loads a BLIP image-captioning model, generates a caption for an uploaded image,
extracts keywords, analyses dominant colours, and maps everything to Spotify-style
audio features via an expanded VIBE_MAP.

Changes vs v1:
  • Conditional BLIP captioning (richer, more specific descriptions)
  • Dominant-colour extraction → warm/cool/dark/bright colour keywords
  • 3× expanded VIBE_MAP — cultural / artistic / style keywords added
  • Direct STYLE_GENRE_MAP: scene styles → music genre tags (bypasses feature scoring)
  • analyse_image() now also returns `genre_hints` for use by LastFmService
"""

from __future__ import annotations

import colorsys
import io
import logging
import re
from collections import Counter
from typing import Dict, List, Optional, Tuple

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

from nltk.corpus import stopwords  # noqa: E402

STOPWORDS: set = set(stopwords.words("english"))

# ---------------------------------------------------------------------------
# STYLE_GENRE_MAP — visual scene / art-style → direct music genre tags
#
# Keys are lowercase substrings that we check against the FULL caption text.
# Values are Last.fm-compatible genre/mood tag lists (ordered: most specific first).
# ---------------------------------------------------------------------------
STYLE_GENRE_MAP: Dict[str, List[str]] = {
    # ── Cultural / world ────────────────────────────────────────────────────
    "zellij":         ["arabic", "world music", "oriental", "ethnic"],
    "zellige":        ["arabic", "world music", "oriental", "ethnic"],
    "mosaic":         ["world music", "ambient", "mediterranean", "ethnic"],
    "arabesque":      ["arabic", "oriental", "world music", "ethnic"],
    "moroccan":       ["arabic", "world music", "oriental", "ethnic"],
    "algerian":       ["arabic", "world music", "rai", "ethnic"],
    "islamic":        ["arabic", "oriental", "world music"],
    "mandala":        ["indian", "world music", "meditation", "ambient"],
    "henna":          ["arabic", "world music", "oriental"],
    "tribal":         ["world music", "ethnic", "tribal"],
    "japanese":       ["japanese", "ambient", "zen", "world music"],
    "kimono":         ["japanese", "world music", "zen"],
    "indian":         ["indian", "bollywood", "world music", "ethnic"],
    "african":        ["afrobeats", "world music", "tribal", "ethnic"],
    "latin":          ["latin", "salsa", "reggaeton", "tropical"],
    "flamenco":       ["flamenco", "spanish", "world music"],
    "celtic":         ["celtic", "folk", "world music"],
    "viking":         ["folk", "epic", "metal"],
    "slavic":         ["folk", "world music", "eastern european"],
    "persian":        ["persian", "world music", "oriental"],

    # ── Hippie / psychedelic / retro ─────────────────────────────────────
    "hippie":         ["psychedelic", "classic rock", "folk", "60s"],
    "psychedelic":    ["psychedelic", "classic rock", "experimental"],
    "tie-dye":        ["psychedelic", "classic rock", "60s", "folk"],
    "tie dye":        ["psychedelic", "classic rock", "60s"],
    "boho":           ["indie folk", "folk", "acoustic"],
    "bohemian":       ["indie folk", "folk", "psychedelic"],
    "peace":          ["folk", "indie", "acoustic", "chill"],
    "retro":          ["classic rock", "retro", "oldies", "vintage"],
    "vintage":        ["classic rock", "retro", "oldies", "jazz"],
    "70s":            ["classic rock", "disco", "funk", "70s"],
    "80s":            ["synthwave", "new wave", "pop", "80s"],
    "90s":            ["alternative rock", "grunge", "rnb", "90s"],
    "disco":          ["disco", "funk", "dance", "70s"],
    "funk":           ["funk", "soul", "rnb"],
    "vaporwave":      ["synthwave", "vaporwave", "electronic", "chillwave"],
    "neon":           ["synthwave", "electronic", "cyberpunk"],

    # ── Electronic / futuristic / urban ──────────────────────────────────
    "cyberpunk":      ["cyberpunk", "synthwave", "electronic", "industrial"],
    "futuristic":     ["electronic", "synthwave", "ambient", "edm"],
    "glitch":         ["electronic", "glitch", "experimental"],
    "graffiti":       ["hip-hop", "urban", "street", "rap"],
    "street art":     ["hip-hop", "urban", "rap"],
    "urban":          ["hip-hop", "rnb", "urban"],
    "city":           ["hip-hop", "rnb", "electronic", "urban"],

    # ── Nature / ambient ─────────────────────────────────────────────────
    "forest":         ["folk", "acoustic", "ambient", "nature"],
    "jungle":         ["world music", "tropical", "ambient"],
    "ocean":          ["ambient", "chill", "acoustic"],
    "beach":          ["reggae", "tropical", "chill", "surf"],
    "tropical":       ["reggae", "tropical", "latin", "chill"],
    "desert":         ["ambient", "world music", "arabic", "electronic"],
    "mountain":       ["folk", "acoustic", "ambient"],
    "snow":           ["ambient", "classical", "acoustic"],
    "rain":           ["lo-fi", "ambient", "acoustic", "chill"],
    "sunset":         ["chill", "indie", "acoustic", "ambient"],
    "sunrise":        ["ambient", "chill", "acoustic"],
    "space":          ["ambient", "electronic", "space rock", "psychedelic"],
    "galaxy":         ["ambient", "electronic", "space rock"],
    "underwater":     ["ambient", "electronic", "chill"],

    # ── Mood / emotion scenes ────────────────────────────────────────────
    "festival":       ["electronic", "dance", "edm", "festival"],
    "concert":        ["rock", "pop", "indie", "live"],
    "party":          ["dance", "pop", "edm", "hip-hop"],
    "wedding":        ["classical", "romantic", "pop", "jazz"],
    "meditation":     ["meditation", "ambient", "zen", "spa"],
    "yoga":           ["meditation", "ambient", "world music"],
    "coffee":         ["lo-fi", "jazz", "acoustic", "indie"],
    "library":        ["classical", "lo-fi", "study", "ambient"],
    "flames":         ["rock", "metal", "intense", "hard rock"],
    "fire":           ["rock", "metal", "intense"],

    # ── Art styles ───────────────────────────────────────────────────────
    "grunge":         ["grunge", "alternative rock", "90s"],
    "gothic":         ["gothic", "dark", "metal", "alternative"],
    "punk":           ["punk", "rock", "alternative"],
    "noir":           ["jazz", "blues", "dark", "cinematic"],
    "watercolor":     ["indie", "folk", "acoustic", "dreamy"],
    "impressionist":  ["classical", "ambient", "jazz"],
    "cubist":         ["experimental", "jazz", "avant-garde"],
    "surreal":        ["experimental", "psychedelic", "art rock"],
    "abstract":       ["experimental", "ambient", "electronic"],
    "minimalist":     ["ambient", "classical", "lo-fi", "minimal"],
    "steampunk":      ["industrial", "folk", "alternative"],
}

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
    "flames":     ( 0.40,  0.05,  20.0, -0.30),
    "energetic":  ( 0.35,  0.20,  22.0, -0.25),
    "electric":   ( 0.35,  0.15,  18.0, -0.20),
    "pulse":      ( 0.30,  0.10,  15.0, -0.15),

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
    "festive":    ( 0.35,  0.40,  20.0, -0.20),
    "playful":    ( 0.25,  0.40,  15.0, -0.10),
    "joyous":     ( 0.25,  0.45,  15.0, -0.10),

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
    "meditative": (-0.35,  0.10, -25.0,  0.45),
    "zen":        (-0.35,  0.10, -25.0,  0.45),

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
    "desert":     (-0.05,  0.00,  -5.0,  0.10),
    "tropical":   ( 0.15,  0.30,  10.0, -0.05),
    "floral":     (-0.10,  0.25,  -5.0,  0.20),
    "garden":     (-0.15,  0.20, -10.0,  0.25),

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
    "eerie":      (-0.05, -0.30, -10.0,  0.10),
    "haunted":    ( 0.00, -0.35, -10.0,  0.05),
    "gothic":     ( 0.10, -0.30, -5.0,   0.00),

    # ── Epic / dramatic ──────────────────────────────────────────────────
    "dramatic":   ( 0.40, -0.10,  15.0, -0.30),
    "majestic":   ( 0.35,  0.10,  10.0, -0.25),
    "vast":       ( 0.20,  0.05,   5.0, -0.10),
    "massive":    ( 0.35, -0.05,  12.0, -0.25),
    "grand":      ( 0.30,  0.05,  10.0, -0.20),
    "epic":       ( 0.40, -0.05,  15.0, -0.25),
    "powerful":   ( 0.35, -0.05,  12.0, -0.20),
    "heroic":     ( 0.35,  0.10,  12.0, -0.20),
    "triumphant": ( 0.35,  0.30,  12.0, -0.20),

    # ── Romantic / intimate ──────────────────────────────────────────────
    "warm":       (-0.10,  0.25,  -8.0,  0.20),
    "soft":       (-0.15,  0.20, -12.0,  0.25),
    "golden":     (-0.05,  0.25,  -5.0,  0.15),
    "intimate":   (-0.15,  0.20, -10.0,  0.25),
    "romantic":   (-0.10,  0.30, -10.0,  0.25),
    "couple":     (-0.05,  0.25,  -5.0,  0.15),
    "love":       (-0.05,  0.35,  -5.0,  0.15),
    "wedding":    (-0.05,  0.35, -10.0,  0.20),
    "tender":     (-0.20,  0.25, -15.0,  0.30),

    # ── Psychedelic / spiritual ──────────────────────────────────────────
    "psychedelic":( 0.20,  0.15,   5.0, -0.10),
    "trippy":     ( 0.20,  0.15,   5.0, -0.10),
    "spiritual":  (-0.20,  0.10, -15.0,  0.35),
    "mystical":   (-0.15,  0.10, -10.0,  0.25),
    "cosmic":     ( 0.10,  0.05,   0.0,  0.00),
    "sacred":     (-0.20,  0.10, -15.0,  0.30),
    "ritual":     (-0.10, -0.10,  -8.0,  0.15),
    "geometric":  ( 0.05,  0.05,   2.0,  0.00),
    "ornate":     ( 0.00,  0.10,  -5.0,  0.10),
    "intricate":  ( 0.00,  0.05,  -5.0,  0.10),
    "pattern":    ( 0.05,  0.05,   2.0,  0.05),
    "mosaic":     ( 0.00,  0.10,  -5.0,  0.10),
    "tiles":      ( 0.00,  0.10,  -5.0,  0.10),

    # ── Abstract / artistic ──────────────────────────────────────────────
    "abstract":   ( 0.05, -0.05,   0.0,  0.05),
    "artistic":   ( 0.05,  0.05,   0.0,  0.05),
    "minimalist": (-0.15, -0.05, -10.0,  0.15),
    "vintage":    (-0.10,  0.05,  -8.0,  0.20),
    "retro":      ( 0.05,  0.10,   5.0,  0.10),
    "neon":       ( 0.25,  0.15,  15.0, -0.20),
    "futuristic": ( 0.20,  0.10,  12.0, -0.20),
    "glitch":     ( 0.20, -0.10,  10.0, -0.20),
    "surreal":    ( 0.10,  0.05,   0.0,  0.05),
    "dreamy":     (-0.10,  0.20,  -8.0,  0.15),
    "fantasy":    (-0.05,  0.15,  -5.0,  0.10),

    # ── Colour-energy keywords (injected by colour analysis) ─────────────
    "saturated":  ( 0.15,  0.20,  10.0, -0.10),
    "muted":      (-0.10, -0.05,  -8.0,  0.15),
    "pastel":     (-0.10,  0.15,  -8.0,  0.15),
    "monochrome": (-0.05, -0.10,  -5.0,  0.10),
    "luminous":   ( 0.15,  0.20,  10.0, -0.10),
    "shadowy":    (-0.10, -0.20, -10.0,  0.15),
    "earthy":     (-0.10,  0.05,  -5.0,  0.20),
}

# Default baseline values (neutral starting point)
_DEFAULTS = {"energy": 0.5, "valence": 0.5, "tempo": 120.0, "acousticness": 0.5}

# ---------------------------------------------------------------------------
# Model singleton (loaded once at startup)
# ---------------------------------------------------------------------------
_processor: Optional[BlipProcessor] = None
_model: Optional[BlipForConditionalGeneration] = None


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
# Caption generation — multi-prompt conditional captioning
# ---------------------------------------------------------------------------

_CONDITIONAL_PROMPTS = [
    "a photo of",
    "the art style shown in this image is",
    "the mood and atmosphere of this image is",
    "the cultural or artistic theme is",
]


def generate_caption(image_bytes: bytes) -> str:
    """
    Run BLIP captioning on raw image bytes.

    Strategy:
    1. Unconditional caption (basic scene description)
    2. Several conditional prompts to tease out style/mood/cultural info
    3. Merge all captions into one rich string for keyword extraction
    """
    _load_model()
    image = Image.open(io.BytesIO(image_bytes)).convert("RGB")

    captions: List[str] = []

    # 1. Unconditional caption
    inputs = _processor(image, return_tensors="pt")  # type: ignore[arg-type]
    output = _model.generate(**inputs, max_new_tokens=60, num_beams=5)  # type: ignore[union-attr]
    unconditional: str = _processor.decode(output[0], skip_special_tokens=True).strip()  # type: ignore[union-attr]
    captions.append(unconditional)
    logger.info("BLIP unconditional: '%s'", unconditional)

    # 2. Conditional captions
    for prompt in _CONDITIONAL_PROMPTS:
        try:
            inputs = _processor(image, text=prompt, return_tensors="pt")  # type: ignore[arg-type]
            output = _model.generate(**inputs, max_new_tokens=40, num_beams=4)  # type: ignore[union-attr]
            cond: str = _processor.decode(output[0], skip_special_tokens=True).strip()  # type: ignore[union-attr]
            # Strip the prompt prefix so we don't duplicate it
            cond = re.sub(r"^" + re.escape(prompt) + r"\s*", "", cond, flags=re.IGNORECASE)
            if cond and len(cond) > 5:
                captions.append(cond)
                logger.info("BLIP conditional ('%s'): '%s'", prompt, cond)
        except Exception as exc:
            logger.debug("Conditional caption failed for prompt '%s': %s", prompt, exc)

    combined = ". ".join(captions)
    logger.info("Combined caption: '%s'", combined)
    return combined


# ---------------------------------------------------------------------------
# Dominant colour analysis
# ---------------------------------------------------------------------------

def _dominant_colour_keywords(image_bytes: bytes) -> List[str]:
    """
    Sample the image colours and return descriptive keywords based on:
    - Average brightness  → 'bright' / 'dark' / 'shadowy'
    - Colour saturation   → 'saturated' / 'vibrant' / 'muted' / 'monochrome' / 'pastel'
    - Dominant hue range  → 'warm' / 'earthy' / 'cool' / 'luminous'
    """
    try:
        img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
        # Downscale for speed
        img = img.resize((64, 64), Image.LANCZOS)
        pixels = list(img.getdata())

        # Convert to HSV
        hsv_vals = [colorsys.rgb_to_hsv(r / 255, g / 255, b / 255) for r, g, b in pixels]
        h_vals = [h for h, s, v in hsv_vals]
        s_vals = [s for h, s, v in hsv_vals]
        v_vals = [v for h, s, v in hsv_vals]

        avg_s = sum(s_vals) / len(s_vals)
        avg_v = sum(v_vals) / len(v_vals)

        keywords: List[str] = []

        # Brightness
        if avg_v > 0.75:
            keywords.append("bright")
        elif avg_v < 0.35:
            keywords.append("shadowy")
            keywords.append("dark")

        # Saturation
        if avg_s > 0.55:
            keywords.append("saturated")
            keywords.append("vibrant")
        elif avg_s < 0.15:
            keywords.append("monochrome")
        elif avg_s < 0.30:
            keywords.append("muted")
        elif avg_s < 0.45:
            keywords.append("pastel")

        # Hue distribution — find dominant hue bucket
        buckets: Counter = Counter()
        for h in h_vals:
            if h < 0.05 or h > 0.95:
                buckets["red"] += 1
            elif h < 0.11:
                buckets["orange"] += 1
            elif h < 0.17:
                buckets["yellow"] += 1
            elif h < 0.42:
                buckets["green"] += 1
            elif h < 0.53:
                buckets["cyan"] += 1
            elif h < 0.70:
                buckets["blue"] += 1
            elif h < 0.83:
                buckets["purple"] += 1
            else:
                buckets["pink"] += 1

        dominant_hue = buckets.most_common(1)[0][0] if buckets else "neutral"
        warm_hues = {"red", "orange", "yellow"}
        cool_hues = {"blue", "cyan", "purple"}
        earth_hues = {"orange", "yellow", "green"}

        if dominant_hue in warm_hues and avg_s > 0.30:
            keywords.append("warm")
        if dominant_hue in cool_hues:
            keywords.append("cool")
        if dominant_hue in earth_hues and avg_s < 0.50:
            keywords.append("earthy")
        if dominant_hue in {"blue", "cyan"} and avg_v > 0.60:
            keywords.append("luminous")

        logger.info(
            "Colour analysis — avg_s=%.2f avg_v=%.2f dominant_hue=%s keywords=%s",
            avg_s, avg_v, dominant_hue, keywords,
        )
        return keywords

    except Exception as exc:
        logger.warning("Colour analysis failed: %s", exc)
        return []


# ---------------------------------------------------------------------------
# Style / cultural genre hint detection
# ---------------------------------------------------------------------------

def detect_genre_hints(caption: str) -> List[str]:
    """
    Scan the full (lowercase) combined caption for STYLE_GENRE_MAP keys.
    Returns deduplicated genre tag lists from all matched styles, ordered by
    specificity (longer / more specific keys matched first).
    """
    lower = caption.lower()
    matched_genres: List[str] = []
    matched_styles: List[str] = []

    # Sort by key length descending so "tie-dye" beats "tie"
    for style, genres in sorted(STYLE_GENRE_MAP.items(), key=lambda x: -len(x[0])):
        if style in lower:
            matched_styles.append(style)
            for g in genres:
                if g not in matched_genres:
                    matched_genres.append(g)

    if matched_styles:
        logger.info("Style/cultural hints detected: %s → genres: %s", matched_styles, matched_genres[:8])

    return matched_genres[:8]


# ---------------------------------------------------------------------------
# Keyword extraction
# ---------------------------------------------------------------------------

def extract_keywords(caption: str) -> List[str]:
    """
    Tokenise caption, POS-tag it, and keep nouns (NN*) + adjectives (JJ*).
    Also perform a direct lookup against VIBE_MAP keys for exact matches.
    Keywords are sorted by VIBE_MAP relevance first, then by position.
    """
    text = re.sub(r"[^a-zA-Z\s]", "", caption.lower())
    tokens = nltk.word_tokenize(text)

    tokens = [t for t in tokens if t not in STOPWORDS and len(t) > 2]

    tagged = nltk.pos_tag(tokens)
    keywords: List[str] = [
        word for word, tag in tagged if tag.startswith(("NN", "JJ", "VB"))
    ]

    seen: set = set()
    unique_keywords: List[str] = []
    for kw in keywords:
        if kw not in seen:
            seen.add(kw)
            unique_keywords.append(kw)

    vibe_hits = [kw for kw in unique_keywords if kw in VIBE_MAP]
    other_kws = [kw for kw in unique_keywords if kw not in VIBE_MAP]
    sorted_kws = vibe_hits + other_kws

    logger.info(
        "Extracted %d keywords (%d VIBE_MAP hits): %s",
        len(sorted_kws), len(vibe_hits), sorted_kws[:10],
    )
    return sorted_kws[:15]


# ---------------------------------------------------------------------------
# VIBE_MAP scoring
# ---------------------------------------------------------------------------

def score_vibe(keywords: List[str]) -> Dict[str, float]:
    """
    Accumulate VIBE_MAP deltas for each keyword using position-decay weighting.
    """
    energy       = _DEFAULTS["energy"]
    valence      = _DEFAULTS["valence"]
    tempo        = _DEFAULTS["tempo"]
    acousticness = _DEFAULTS["acousticness"]

    matched: List[str] = []
    hit_idx = 0

    for kw in keywords:
        if kw in VIBE_MAP:
            weight = 1.0 / (1.0 + hit_idx * 0.15)
            de, dv, dt, da = VIBE_MAP[kw]
            energy       += de * weight
            valence      += dv * weight
            tempo        += dt * weight
            acousticness += da * weight
            matched.append(kw)
            hit_idx += 1

    logger.info(
        "VIBE_MAP scoring — %d/%d matched: %s | raw: E=%.3f V=%.3f T=%.1f A=%.3f",
        len(matched), len(keywords), matched,
        energy, valence, tempo, acousticness,
    )

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
    Full pipeline: bytes → caption + colour analysis → keywords → vibe scores + genre hints.
    Returns dict ready to be serialised as VibeResponse.
    """
    caption       = generate_caption(image_bytes)
    colour_kws    = _dominant_colour_keywords(image_bytes)
    genre_hints   = detect_genre_hints(caption)
    keywords      = extract_keywords(caption)

    # Inject colour keywords into keyword list (they're already in VIBE_MAP)
    all_keywords = keywords + [ck for ck in colour_kws if ck not in keywords]

    scores = score_vibe(all_keywords)

    # If genre hints were found, prepend them to keywords so the PHP side can use them
    # We store genre_hints separately so LastFmService can prioritise them
    return {
        "caption":      caption,
        "keywords":     all_keywords[:15],
        "genre_hints":  genre_hints,
        **scores,
    }
