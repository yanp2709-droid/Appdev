import base64
import json
from datetime import timedelta
from urllib.parse import urlencode

import requests
from django.conf import settings
from django.contrib.auth.models import User
from django.db.models import Count
from django.shortcuts import redirect
from django.utils import timezone
from rest_framework import permissions, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response
from rest_framework.views import APIView
from rest_framework_simplejwt.tokens import RefreshToken

from .models import ArtistPreference, FavoriteTrack, MoodLog, PlayEvent, UserProfile
from .serializers import ArtistPreferenceSerializer, FavoriteTrackSerializer, MoodLogSerializer, ProfileSerializer
from .services import build_ai_message, predict_emotion, recommend_tracks_for_emotion


class HealthView(APIView):
    permission_classes = [permissions.AllowAny]

    def get(self, request):
        return Response({"status": "ok", "service": "EmoTune API"})


class SpotifyLoginView(APIView):
    permission_classes = [permissions.AllowAny]

    def get(self, request):
        params = {
            "client_id": settings.SPOTIFY_CLIENT_ID,
            "response_type": "code",
            "redirect_uri": settings.SPOTIFY_REDIRECT_URI,
            "scope": settings.SPOTIFY_SCOPES,
            "show_dialog": "true",
        }
        auth_url = f"https://accounts.spotify.com/authorize?{urlencode(params)}"
        return Response({"auth_url": auth_url})


class SpotifyCallbackView(APIView):
    permission_classes = [permissions.AllowAny]

    def get(self, request):
        code = request.query_params.get("code")
        if not code:
            return Response({"detail": "Missing code"}, status=status.HTTP_400_BAD_REQUEST)

        basic = base64.b64encode(f"{settings.SPOTIFY_CLIENT_ID}:{settings.SPOTIFY_CLIENT_SECRET}".encode()).decode()
        token_response = requests.post(
            "https://accounts.spotify.com/api/token",
            headers={"Authorization": f"Basic {basic}"},
            data={
                "grant_type": "authorization_code",
                "code": code,
                "redirect_uri": settings.SPOTIFY_REDIRECT_URI,
            },
            timeout=20,
        )
        if not token_response.ok:
            return Response(token_response.json(), status=status.HTTP_400_BAD_REQUEST)

        token_data = token_response.json()
        access_token = token_data.get("access_token")

        profile_response = requests.get(
            "https://api.spotify.com/v1/me",
            headers={"Authorization": f"Bearer {access_token}"},
            timeout=20,
        )
        profile_response.raise_for_status()
        profile = profile_response.json()

        username = f"spotify_{profile['id']}"
        user, _ = User.objects.get_or_create(
            username=username,
            defaults={
                "email": profile.get("email", f"{profile['id']}@spotify.local"),
                "first_name": profile.get("display_name", ""),
            },
        )

        user_profile, _ = UserProfile.objects.get_or_create(user=user)
        user_profile.spotify_user_id = profile.get("id", "")
        user_profile.display_name = profile.get("display_name", "")
        images = profile.get("images", [])
        user_profile.profile_image = images[0]["url"] if images else ""
        user_profile.save()

        refresh = RefreshToken.for_user(user)
        payload = {
            "access": str(refresh.access_token),
            "refresh": str(refresh),
            "spotify_access": access_token,
            "spotify_refresh": token_data.get("refresh_token", ""),
            "spotify_expires_in": token_data.get("expires_in", 3600),
        }
        query = urlencode({"payload": json.dumps(payload)})
        return redirect(f"{settings.FRONTEND_REDIRECT_URI}?{query}")


class RecommendationView(APIView):
    def post(self, request):
        prompt = request.data.get("prompt", "").strip()
        if not prompt:
            return Response({"detail": "Prompt is required."}, status=status.HTTP_400_BAD_REQUEST)

        emotion = predict_emotion(prompt)
        ai_message = build_ai_message(emotion)
        tracks = recommend_tracks_for_emotion(request.user, emotion)

        mood_log = MoodLog.objects.create(
            user=request.user,
            prompt=prompt,
            predicted_mood=emotion,
            ai_message=ai_message,
            playlist_name=f"{emotion.title()} Mix",
            recommended_tracks=tracks,
        )

        return Response(
            {
                "id": mood_log.id,
                "emotion": emotion,
                "ai_message": ai_message,
                "playlist_name": mood_log.playlist_name,
                "tracks": tracks,
            }
        )


class HistoryView(APIView):
    def get(self, request):
        logs = MoodLog.objects.filter(user=request.user).order_by("-created_at")[:50]
        return Response(MoodLogSerializer(logs, many=True).data)


class FavoritesView(APIView):
    def get(self, request):
        favorites = FavoriteTrack.objects.filter(user=request.user).order_by("-created_at")
        return Response(FavoriteTrackSerializer(favorites, many=True).data)

    def post(self, request):
        serializer = FavoriteTrackSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        FavoriteTrack.objects.update_or_create(
            user=request.user,
            track_id=serializer.validated_data["track_id"],
            defaults=serializer.validated_data,
        )
        return Response({"detail": "Saved to favorites."}, status=status.HTTP_201_CREATED)

    def delete(self, request):
        track_id = request.data.get("track_id")
        FavoriteTrack.objects.filter(user=request.user, track_id=track_id).delete()
        return Response(status=status.HTTP_204_NO_CONTENT)


class ArtistPreferenceView(APIView):
    def get(self, request):
        items = ArtistPreference.objects.filter(user=request.user)
        return Response(ArtistPreferenceSerializer(items, many=True).data)

    def post(self, request):
        serializer = ArtistPreferenceSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        ArtistPreference.objects.update_or_create(
            user=request.user,
            artist_id=serializer.validated_data["artist_id"],
            defaults={"artist_name": serializer.validated_data["artist_name"]},
        )
        return Response({"detail": "Artist preference saved."}, status=status.HTTP_201_CREATED)

    def delete(self, request):
        artist_id = request.data.get("artist_id")
        ArtistPreference.objects.filter(user=request.user, artist_id=artist_id).delete()
        return Response(status=status.HTTP_204_NO_CONTENT)


class PlayEventView(APIView):
    def post(self, request):
        PlayEvent.objects.create(
            user=request.user,
            track_id=request.data.get("track_id", ""),
            track_name=request.data.get("track_name", ""),
            played_seconds=int(request.data.get("played_seconds", 0)),
            mood=request.data.get("mood", "mixed"),
        )

        hour_plays = PlayEvent.objects.filter(
            user=request.user,
            created_at__gte=timezone.now() - timedelta(hours=1),
        )
        seconds_total = sum(item.played_seconds for item in hour_plays)

        uplift = None
        if seconds_total >= 1800:
            uplift = {
                "message": "Feel better? Here is one uplifting song for you.",
                "track": recommend_tracks_for_emotion(request.user, "happy", limit=1)[0],
            }

        return Response({"detail": "Play event recorded.", "uplift": uplift})


class ProfileView(APIView):
    def get(self, request):
        profile, _ = UserProfile.objects.get_or_create(user=request.user)
        data = ProfileSerializer(profile).data

        mood_data = MoodLog.objects.filter(user=request.user).values("predicted_mood").annotate(total=Count("id")).order_by("-total")
        data["mood_distribution"] = list(mood_data)
        return Response(data)

    def patch(self, request):
        profile, _ = UserProfile.objects.get_or_create(user=request.user)
        profile.display_name = request.data.get("display_name", profile.display_name)
        profile.preferred_theme = request.data.get("preferred_theme", profile.preferred_theme)
        profile.save()

        request.user.email = request.data.get("email", request.user.email)
        new_password = request.data.get("new_password")
        if new_password:
            request.user.set_password(new_password)
        request.user.save()
        return Response({"detail": "Profile updated."})


class AdminDashboardView(APIView):
    permission_classes = [permissions.IsAdminUser]

    def get(self, request):
        active_cutoff = timezone.now() - timedelta(minutes=15)
        active_users = PlayEvent.objects.filter(created_at__gte=active_cutoff).values("user").distinct().count()
        total_users = User.objects.count()

        month_stats = (
            MoodLog.objects.extra(select={"month": "strftime('%%Y-%%m', created_at)"})
            .values("month")
            .annotate(total=Count("id"))
            .order_by("month")
        )
        mood_distribution = MoodLog.objects.values("predicted_mood").annotate(total=Count("id")).order_by("-total")
        common_moods = list(mood_distribution[:5])

        return Response(
            {
                "active_users": active_users,
                "total_users": total_users,
                "playlist_generation_monthly": list(month_stats),
                "common_moods": common_moods,
                "mood_distribution": list(mood_distribution),
            }
        )


class AdminAccountsView(APIView):
    permission_classes = [permissions.IsAdminUser]

    def get(self, request):
        query = request.query_params.get("q", "")
        users = User.objects.all().order_by("id")
        if query:
            users = users.filter(username__icontains=query)
        payload = [
            {
                "id": user.id,
                "username": user.username,
                "email": user.email,
                "is_staff": user.is_staff,
                "date_joined": user.date_joined,
            }
            for user in users[:200]
        ]
        return Response(payload)

    def delete(self, request):
        user_id = request.data.get("user_id")
        User.objects.filter(id=user_id).delete()
        return Response(status=status.HTTP_204_NO_CONTENT)


@api_view(["GET"])
@permission_classes([permissions.AllowAny])
def emotion_labels(_request):
    return Response(
        {
            "labels": [
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
        }
    )
