import base64
from collections import Counter
from datetime import timedelta
from pathlib import Path
from typing import Dict, List

import requests
from django.conf import settings
from django.db.models import Count, Sum
from django.utils import timezone

from .models import ArtistPreference, PlayEvent

try:
    from transformers import pipeline
except Exception:  # pragma: no cover
    pipeline = None

EMOTION_LABELS = [
    "happy",
    "sad",
    "angry",
    "motivational",
    "fear",
    "depressing",
    "surprising",
    "stressed",
    "calm",
    "lonely",
    "romantic",
    "nostalgic",
    "mixed",
]

EMOTION_KEYWORDS = {
    "happy": ["happy", "joy", "great", "excited", "thankful"],
    "sad": ["sad", "down", "cry", "heartbroken", "blue"],
    "angry": ["angry", "mad", "furious", "annoyed"],
    "motivational": ["motivate", "focus", "discipline", "hustle", "goal"],
    "fear": ["fear", "afraid", "panic", "anxious"],
    "depressing": ["empty", "depressed", "hopeless", "worthless"],
    "surprising": ["surprised", "unexpected", "shocked"],
    "stressed": ["stress", "overwhelmed", "pressure", "burnout"],
    "calm": ["calm", "relax", "peace", "breathe"],
    "lonely": ["lonely", "alone", "isolated"],
    "romantic": ["love", "romantic", "crush", "date"],
    "nostalgic": ["nostalgic", "memories", "throwback", "remember"],
}

EMOTION_RESPONSES = {
    "happy": "Love that energy. Here is a playlist to keep your mood up.",
    "sad": "I hear you. Let us start with something comforting.",
    "angry": "I get it. Here are tracks to release tension safely.",
    "motivational": "Locked in mode. Let us build momentum.",
    "fear": "You are not alone. Let us slow things down together.",
    "depressing": "Thanks for sharing honestly. I will start gentle and supportive.",
    "surprising": "Big emotions today. Here is a balanced mix.",
    "stressed": "Let us lower the pressure with calmer tracks.",
    "calm": "Staying steady. Here is a smooth calm playlist.",
    "lonely": "I am here with you. These songs can help you feel connected.",
    "romantic": "Setting the vibe. Here is your romantic mix.",
    "nostalgic": "Let us revisit memories with warm throwbacks.",
    "mixed": "You are feeling a mix. I built a balanced set for you.",
}

SEED_GENRES = {
    "happy": ["pop", "dance"],
    "sad": ["acoustic", "piano"],
    "angry": ["rock", "metal"],
    "motivational": ["work-out", "hip-hop"],
    "fear": ["ambient", "chill"],
    "depressing": ["indie", "singer-songwriter"],
    "surprising": ["alternative", "electro"],
    "stressed": ["chill", "sleep"],
    "calm": ["lo-fi", "jazz"],
    "lonely": ["acoustic", "indie"],
    "romantic": ["r-n-b", "soul"],
    "nostalgic": ["old-school", "classic"],
    "mixed": ["pop", "indie"],
}

_predictor = None


def _load_bert_predictor():
    global _predictor
    if _predictor is not None:
        return _predictor
    if pipeline is None:
        return None

    model_dir = Path(__file__).resolve().parent.parent / "ml" / "artifacts"
    if model_dir.exists():
        try:
            _predictor = pipeline("text-classification", model=str(model_dir), tokenizer=str(model_dir))
            return _predictor
        except Exception:
            return None
    return None


def predict_emotion(prompt: str) -> str:
    predictor = _load_bert_predictor()
    if predictor is not None:
        try:
            result = predictor(prompt, truncation=True)[0]
            label = str(result.get("label", "")).lower().replace("label_", "")
            if label in EMOTION_LABELS:
                return label
        except Exception:
            pass

    lowered = prompt.lower()
    scores = Counter()
    for emotion, words in EMOTION_KEYWORDS.items():
        for word in words:
            if word in lowered:
                scores[emotion] += 1
    if not scores:
        return "mixed"
    return scores.most_common(1)[0][0]


def build_ai_message(emotion: str) -> str:
    return EMOTION_RESPONSES.get(emotion, EMOTION_RESPONSES["mixed"])


def spotify_app_token() -> str:
    if not settings.SPOTIFY_CLIENT_ID or not settings.SPOTIFY_CLIENT_SECRET:
        return ""
    basic = base64.b64encode(f"{settings.SPOTIFY_CLIENT_ID}:{settings.SPOTIFY_CLIENT_SECRET}".encode()).decode()
    response = requests.post(
        "https://accounts.spotify.com/api/token",
        headers={"Authorization": f"Basic {basic}"},
        data={"grant_type": "client_credentials"},
        timeout=20,
    )
    response.raise_for_status()
    return response.json().get("access_token", "")


def recommend_tracks_for_emotion(user, emotion: str, limit: int = 8) -> List[Dict]:
    token = spotify_app_token()
    genres = SEED_GENRES.get(emotion, ["pop"])
    preferred_artists = list(ArtistPreference.objects.filter(user=user).values_list("artist_id", flat=True)[:2])

    repeated_track_id = (
        PlayEvent.objects.filter(user=user, mood=emotion)
        .values("track_id", "track_name")
        .annotate(cnt=Count("id"))
        .order_by("-cnt")
        .first()
    )

    tracks = []
    if token:
        params = {"seed_genres": ",".join(genres[:2]), "limit": limit, "market": "US"}
        if preferred_artists:
            params["seed_artists"] = ",".join(preferred_artists)

        response = requests.get(
            "https://api.spotify.com/v1/recommendations",
            headers={"Authorization": f"Bearer {token}"},
            params=params,
            timeout=20,
        )
        if response.ok:
            for item in response.json().get("tracks", []):
                tracks.append(
                    {
                        "id": item["id"],
                        "name": item["name"],
                        "artist": item["artists"][0]["name"] if item.get("artists") else "Unknown",
                        "preview_url": item.get("preview_url") or "",
                        "image": item.get("album", {}).get("images", [{}])[0].get("url", ""),
                    }
                )

    if not tracks:
        tracks = [
            {
                "id": f"mock-{emotion}-{i}",
                "name": f"{emotion.title()} Track {i + 1}",
                "artist": "EmoTune AI",
                "preview_url": "",
                "image": "",
            }
            for i in range(limit)
        ]

    if repeated_track_id:
        track = next((t for t in tracks if t["id"] == repeated_track_id["track_id"]), None)
        if track:
            tracks.remove(track)
            tracks.insert(0, track)

    return tracks


def should_prompt_feel_better(user, within_minutes: int = 120) -> bool:
    since = timezone.now() - timedelta(minutes=within_minutes)
    total_seconds = PlayEvent.objects.filter(user=user, created_at__gte=since).aggregate(total=Sum("played_seconds")).get("total")
    return bool(total_seconds and total_seconds >= 1800)
