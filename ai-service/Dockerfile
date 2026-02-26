# ─────────────────────────────────────────────────────────────────────────────
# Vibe Playlist Generator — AI Microservice Dockerfile
# Base image: Python 3.11 slim for a lean container
# Port:       8001
# ─────────────────────────────────────────────────────────────────────────────

FROM python:3.11-slim

# Keeps Python from buffering stdout/stderr (important for Docker log streaming)
ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    # HuggingFace cache inside the container (mountable as a volume)
    HF_HOME=/app/.cache/huggingface \
    TRANSFORMERS_CACHE=/app/.cache/huggingface \
    # Prevents HF from phoning home for telemetry
    TRANSFORMERS_OFFLINE=0

WORKDIR /app

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        libglib2.0-0 \
        libsm6 \
        libxrender1 \
        libxext6 \
        libgl1-mesa-glx \
    && rm -rf /var/lib/apt/lists/*

# ── Python dependencies ───────────────────────────────────────────────────────
COPY requirements.txt .
RUN pip install --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# ── NLTK data (downloaded at build time — avoids first-request latency) ──────
RUN python -c "\
import nltk; \
nltk.download('punkt', quiet=True); \
nltk.download('punkt_tab', quiet=True); \
nltk.download('stopwords', quiet=True); \
nltk.download('averaged_perceptron_tagger', quiet=True); \
nltk.download('averaged_perceptron_tagger_eng', quiet=True)"

# ── Application source ────────────────────────────────────────────────────────
COPY . .

# ── Non-root user for security ────────────────────────────────────────────────
RUN useradd -m -u 1001 vibeuser && \
    chown -R vibeuser:vibeuser /app
USER vibeuser

# ── Expose port ───────────────────────────────────────────────────────────────
EXPOSE 8001

# ── Health check ──────────────────────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://localhost:8001/health')" \
    || exit 1

# ── Start server ──────────────────────────────────────────────────────────────
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "1"]
