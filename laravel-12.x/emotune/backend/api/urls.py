from django.urls import path

from .views import (
    AdminAccountsView,
    AdminDashboardView,
    ArtistPreferenceView,
    FavoritesView,
    HealthView,
    HistoryView,
    PlayEventView,
    ProfileView,
    RecommendationView,
    SpotifyCallbackView,
    SpotifyLoginView,
    emotion_labels,
)

urlpatterns = [
    path("health/", HealthView.as_view()),
    path("labels/", emotion_labels),
    path("auth/spotify/login/", SpotifyLoginView.as_view()),
    path("auth/spotify/callback/", SpotifyCallbackView.as_view()),
    path("recommendations/", RecommendationView.as_view()),
    path("history/", HistoryView.as_view()),
    path("favorites/", FavoritesView.as_view()),
    path("artists/", ArtistPreferenceView.as_view()),
    path("play-events/", PlayEventView.as_view()),
    path("profile/", ProfileView.as_view()),
    path("admin/dashboard/", AdminDashboardView.as_view()),
    path("admin/accounts/", AdminAccountsView.as_view()),
]
