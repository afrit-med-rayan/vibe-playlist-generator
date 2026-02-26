"""
main.py — Vibe Playlist Generator · AI Microservice
────────────────────────────────────────────────────
Exposes:
  POST /analyze-image   → VibeResponse JSON
  GET  /health          → {"status": "ok"}
  GET  /docs            → Swagger UI (auto-generated)
"""

import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import RedirectResponse

from models import HealthResponse, VibeResponse
from vibe_analyzer import analyze_image

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s — %(message)s",
)
logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Lifespan: warm-up BLIP model at startup so the first request isn't slow
# ---------------------------------------------------------------------------
@asynccontextmanager
async def lifespan(app: FastAPI):
    logger.info("🚀 AI Service starting — warming up BLIP model…")
    try:
        from vibe_analyzer import _load_model
        _load_model()
        logger.info("✅ BLIP model ready")
    except Exception as exc:
        logger.warning("⚠️  Model warm-up failed (will retry on first request): %s", exc)
    yield
    logger.info("🛑 AI Service shutting down")


# ---------------------------------------------------------------------------
# App
# ---------------------------------------------------------------------------
app = FastAPI(
    title="Vibe Playlist Generator — AI Service",
    description=(
        "Accepts an image, generates a BLIP caption, extracts mood keywords, "
        "and scores Spotify audio features (energy, valence, tempo, acousticness)."
    ),
    version="1.0.0",
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],   # restrict in production
    allow_methods=["*"],
    allow_headers=["*"],
)


# ---------------------------------------------------------------------------
# Routes
# ---------------------------------------------------------------------------

@app.get("/", include_in_schema=False)
async def root():
    """Redirect root to the interactive API docs."""
    return RedirectResponse(url="/docs")


@app.get("/health", response_model=HealthResponse, tags=["Utility"])
async def health_check():
    """Simple liveness probe."""
    return HealthResponse(status="ok")


@app.get("/info", tags=["Utility"])
async def service_info():
    """Returns service metadata — useful for debugging and monitoring."""
    return {
        "service": "vibe-ai-service",
        "version": "1.0.0",
        "description": "BLIP image captioning + VIBE_MAP audio feature scoring",
        "endpoints": ["/analyze-image", "/health", "/info", "/docs"],
    }


@app.post("/analyze-image", response_model=VibeResponse, tags=["Analysis"])
async def analyze_image_endpoint(
    image: UploadFile = File(..., description="Image file to analyse (JPEG, PNG, WEBP…)"),
):
    """
    Upload an image and receive structured vibe data.

    - Runs BLIP captioning to describe the scene
    - Extracts mood/scene keywords via NLTK POS-tagging
    - Scores Spotify audio features through VIBE_MAP keyword matching
    """
    # --- Validate content type ---
    if image.content_type and not image.content_type.startswith("image/"):
        raise HTTPException(
            status_code=415,
            detail=f"Unsupported media type: {image.content_type}. Please upload an image.",
        )

    # --- Read bytes ---
    image_bytes = await image.read()
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty file received.")

    logger.info(
        "Received image '%s' (%.1f KB) for analysis",
        image.filename,
        len(image_bytes) / 1024,
    )

    # --- Run analysis pipeline ---
    try:
        result = analyze_image(image_bytes)
    except Exception as exc:
        logger.exception("Analysis pipeline failed")
        raise HTTPException(status_code=500, detail=f"Analysis failed: {str(exc)}")

    logger.info(
        "Analysis complete — caption: '%s' | keywords: %s | energy=%.2f valence=%.2f",
        result["caption"],
        result["keywords"][:5],
        result["energy"],
        result["valence"],
    )

    return VibeResponse(**result)
