# EmoTune Capstone (Flutter + Django + BERT + Spotify)

This folder contains an MVP implementation of **EmoTune: Sentiment-Driven AI Music Recommender for Emotional Well-being**.

## Stack

- Frontend: Flutter (Web-ready)
- Backend: Django + Django REST Framework
- ML: BERT fine-tuning script (Transformers)
- Music source: Spotify Web API

## Menus implemented

- Home
- Favorite
- Recommendation
- History
- Profile

Light and dark mode are included.

## Key implemented features

- Prompt-based mood detection and playlist recommendation
- AI response message after each prompt
- Artist preferences CRUD (backend API)
- History API and History page
- Adaptive recommendation (repeats preferred/repeated tracks first)
- Long listening detection with `Feel better?` uplift recommendation trigger
- Favorites CRUD and Favorite page
- Profile endpoint with mood pie-chart data
- Change profile details and credentials endpoint
- Spotify OAuth login flow
- In-app audio controls (play/pause) using preview URLs
- Admin dashboard APIs (active users, totals, monthly stats, mood distribution)
- Admin account handling APIs (list/search/delete)

## Important security note

Your Spotify credentials were shared in chat. Treat them as compromised and rotate them in Spotify Developer Dashboard immediately.

## Quick start

1. Backend setup: see [backend/README.md](backend/README.md)
2. Frontend setup: see [docs/setup.md](docs/setup.md)
3. Model training: see [docs/model_training.md](docs/model_training.md)

## Project layout

- `backend/`: Django APIs, models, admin APIs, Spotify integration
- `frontend/`: Flutter web app
- `backend/data/`: starter labeled emotion dataset
- `backend/ml/`: BERT training scripts
- `docs/`: setup + training + results docs
