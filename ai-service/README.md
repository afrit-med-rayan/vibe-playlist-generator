# AI Service — Vibe Playlist Generator

A lightweight Python microservice that analyzes images and returns Spotify-compatible audio feature scores.

## Stack
- **FastAPI** — async web framework
- **BLIP** (`Salesforce/blip-image-captioning-base`) — image captioning
- **NLTK** — POS-tagging for keyword extraction
- **PyTorch** (CPU) — model inference

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/health` | Liveness probe |
| `POST` | `/analyze-image` | Upload image → vibe JSON |
| `GET`  | `/docs` | Swagger UI |

### Response shape (`POST /analyze-image`)
```json
{
  "caption":      "a serene lake surrounded by misty mountains",
  "keywords":     ["lake", "misty", "mountains", "serene"],
  "energy":       0.25,
  "valence":      0.55,
  "tempo":        95.0,
  "acousticness": 0.78
}
```

## Running locally

```bash
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

## Docker

```bash
# Build
docker build -t vibe-ai-service .

# Run
docker run -p 8001:8001 vibe-ai-service

# With model cache volume (avoids re-downloading BLIP every start)
docker run -p 8001:8001 -v $(pwd)/.cache:/app/.cache vibe-ai-service
```

## Environment variables

| Variable | Default | Description |
|---|---|---|
| `HF_HOME` | `/app/.cache/huggingface` | HuggingFace model cache path |
| `TRANSFORMERS_OFFLINE` | `0` | Set to `1` to disable model downloads |

## VIBE_MAP

Keywords extracted from the BLIP caption are matched against a built-in dictionary of 60+ mood/scene terms. Each keyword contributes signed deltas to the four Spotify audio features, which are then clamped to valid ranges `[0, 1]` (or `[40, 220]` for tempo).
