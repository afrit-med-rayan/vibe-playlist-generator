# 🎵 VibeCraft — AI-Powered Vibe Playlist Generator

> Upload a photo → AI detects the mood → Last.fm tags the vibe → Deezer generates a playable playlist.

[![PHP](https://img.shields.io/badge/PHP-8.3-purple?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11-red?logo=laravel)](https://laravel.com)
[![Python](https://img.shields.io/badge/Python-3.13-blue?logo=python)](https://python.org)
[![FastAPI](https://img.shields.io/badge/FastAPI-✓-green?logo=fastapi)](https://fastapi.tiangolo.com)

---

## 🏗 Architecture

```
📸 User uploads image
      ↓
🤖 AI Microservice (Python / FastAPI + BLIP vision model)
   └─ caption, keywords, energy, valence, tempo, acousticness
      ↓
🔗 Last.fm API  (Primary — mood tag discovery)
   └─ tag.getTopTracks(mood) → ranked track list
      ↓
🎧 Deezer API   (Secondary — playable content)
   └─ search(track+artist) → artwork + 30s MP3 preview + deep-link
      ↓
✅ Result page with audio previews in the browser
```

### Why Last.fm + Deezer instead of Spotify?

Spotify's Web API now requires a **Premium subscription** to register an application in many regions. Rather than block on a vendor limitation, this project demonstrates a **modular, vendor-agnostic architecture**:

| Layer | Provider | Auth Required? |
|-------|----------|---------------|
| Image analysis | Custom Python/BLIP AI | — |
| Mood tags | Last.fm (free API) | API key only |
| Playable tracks | Deezer (open search) | **None** |

This pattern — decoupling mood detection from playlist delivery — means any provider (Spotify, Apple Music, YouTube) can be swapped in without touching the AI or tagging layers.

---

## 🧠 AI Microservice

Located in `ai-service/`, built with **FastAPI + BLIP (Salesforce/blip-image-captioning-base) + NLTK**.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Liveness probe |
| `/analyze-image` | POST | Upload image → vibe JSON |
| `/docs` | GET | Swagger UI |

**Response shape:**
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

---

## 🚀 Running Locally

### Requirements
- PHP 8.3+, Composer
- Node 18+, npm
- Python 3.10+
- MySQL

### Setup

```bash
# 1. Clone & install PHP deps
git clone https://github.com/afrit-med-rayan/vibe-playlist-generator.git
cd vibe-playlist-generator
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env: set DB credentials + LASTFM_API_KEY

# 3. Run migrations
php artisan migrate

# 4. Install & build frontend
npm install

# 5. Install AI service deps
cd ai-service
python -m venv venv
venv\Scripts\activate   # Windows
pip install -r requirements.txt
cd ..
```

### Start all services

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite
npm run dev

# Terminal 3 — AI microservice
cd ai-service
venv\Scripts\python -m uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

Open [http://localhost:8000](http://localhost:8000)

---

## 🔑 Environment Variables

| Variable | Description |
|----------|-------------|
| `DB_*` | MySQL connection settings |
| `LASTFM_API_KEY` | [Get free key →](https://www.last.fm/api/account/create) |
| `AI_SERVICE_URL` | Default: `http://localhost:8001` |

---

## 📁 Project Structure

```
vibe-playlist-generator/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php      # Local register/login/logout
│   │   └── VibeController.php      # AI → Last.fm → Deezer pipeline
│   └── Services/
│       ├── LastFmService.php       # Mood tags + top tracks
│       └── DeezerService.php       # Cross-reference + preview URLs
├── ai-service/                     # Python FastAPI microservice
│   ├── main.py
│   ├── vibe_analyzer.py
│   └── requirements.txt
├── resources/views/
│   ├── auth/                       # register + login
│   ├── vibe/result.blade.php       # Playlist result with audio previews
│   └── dashboard.blade.php
└── database/migrations/
```

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Blade + Vanilla CSS (glassmorphism) |
| AI Service | Python 3, FastAPI, BLIP, NLTK, PyTorch |
| Music discovery | Last.fm free API |
| Playlist generation | Deezer open API |
| Database | MySQL |

---

*Built as a portfolio project demonstrating modular AI + music API integration.*
