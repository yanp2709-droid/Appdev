from django.contrib import admin

from .models import ArtistPreference, FavoriteTrack, MoodLog, PlayEvent, UserProfile

admin.site.register(UserProfile)
admin.site.register(MoodLog)
admin.site.register(ArtistPreference)
admin.site.register(FavoriteTrack)
admin.site.register(PlayEvent)
