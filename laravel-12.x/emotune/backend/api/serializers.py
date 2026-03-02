from rest_framework import serializers

from .models import ArtistPreference, FavoriteTrack, MoodLog, PlayEvent, UserProfile


class ArtistPreferenceSerializer(serializers.ModelSerializer):
    class Meta:
        model = ArtistPreference
        fields = ["id", "artist_id", "artist_name"]


class FavoriteTrackSerializer(serializers.ModelSerializer):
    class Meta:
        model = FavoriteTrack
        fields = ["id", "track_id", "track_name", "artist_name", "preview_url", "image_url", "created_at"]


class MoodLogSerializer(serializers.ModelSerializer):
    class Meta:
        model = MoodLog
        fields = ["id", "prompt", "predicted_mood", "ai_message", "playlist_name", "recommended_tracks", "created_at"]


class PlayEventSerializer(serializers.ModelSerializer):
    class Meta:
        model = PlayEvent
        fields = ["id", "track_id", "track_name", "played_seconds", "mood", "created_at"]


class ProfileSerializer(serializers.ModelSerializer):
    username = serializers.CharField(source="user.username")
    email = serializers.EmailField(source="user.email")

    class Meta:
        model = UserProfile
        fields = ["username", "email", "display_name", "profile_image", "preferred_theme"]
