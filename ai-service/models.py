from pydantic import BaseModel, Field
from typing import List


class VibeResponse(BaseModel):
    """Structured response returned by /analyze-image."""

    caption: str = Field(..., description="BLIP-generated image caption (multi-prompt merged)")
    keywords: List[str] = Field(..., description="Extracted mood/scene keywords (VIBE_MAP hits first)")
    genre_hints: List[str] = Field(
        default=[],
        description=(
            "Direct music genre/style tags derived from cultural/artistic scene detection "
            "(e.g. 'arabic', 'psychedelic', 'folk'). Empty list if no style match found."
        ),
    )
    energy: float = Field(..., ge=0.0, le=1.0, description="Spotify energy attribute (0–1)")
    valence: float = Field(..., ge=0.0, le=1.0, description="Spotify valence (musical positiveness, 0–1)")
    tempo: float = Field(..., ge=40.0, le=220.0, description="Estimated tempo in BPM")
    acousticness: float = Field(..., ge=0.0, le=1.0, description="Spotify acousticness attribute (0–1)")


class HealthResponse(BaseModel):
    status: str = "ok"
