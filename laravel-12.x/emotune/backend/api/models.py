from django.conf import settings
from django.db import models

EMOTION_CHOICES = [
    ("happy", "Happy"),
    ("sad", "Sad"),
    ("angry", "Angry"),
    ("motivational", "Motivational"),
    ("fear", "Fear"),
    ("depressing", "Depressing"),
    ("surprising", "Surprising"),
    ("stressed", "Stressed"),
    ("calm", "Calm"),
    ("lonely", "Lonely"),
    ("romantic", "Romantic"),
    ("nostalgic", "Nostalgic"),
    ("mixed", "Mixed"),
]


class UserProfile(models.Model):
    user = models.OneToOneField(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    spotify_user_id = models.CharField(max_length=128, blank=True)
    display_name = models.CharField(max_length=128, blank=True)
    profile_image = models.URLField(blank=True)
    preferred_theme = models.CharField(max_length=16, default="dark")


class ArtistPreference(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    artist_id = models.CharField(max_length=64)
    artist_name = models.CharField(max_length=255)

    class Meta:
        unique_together = ("user", "artist_id")


class MoodLog(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    prompt = models.TextField()
    predicted_mood = models.CharField(max_length=32, choices=EMOTION_CHOICES)
    ai_message = models.TextField()
    playlist_name = models.CharField(max_length=255, blank=True)
    recommended_tracks = models.JSONField(default=list)
    created_at = models.DateTimeField(auto_now_add=True)


class FavoriteTrack(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    track_id = models.CharField(max_length=64)
    track_name = models.CharField(max_length=255)
    artist_name = models.CharField(max_length=255)
    preview_url = models.URLField(blank=True)
    image_url = models.URLField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ("user", "track_id")


class PlayEvent(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    track_id = models.CharField(max_length=64)
    track_name = models.CharField(max_length=255)
    played_seconds = models.PositiveIntegerField(default=0)
    mood = models.CharField(max_length=32, choices=EMOTION_CHOICES)
    created_at = models.DateTimeField(auto_now_add=True)
