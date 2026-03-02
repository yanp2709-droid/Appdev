# Setup Guide

## 1) Backend (Django)

```powershell
cd emotune\backend
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
python manage.py makemigrations
python manage.py migrate
python manage.py createsuperuser
python manage.py runserver
```

Backend URL: `http://127.0.0.1:8000`

## 2) Configure Spotify App

In Spotify Developer Dashboard set redirect URIs:

- `http://127.0.0.1:8000/api/auth/spotify/callback`
- `http://localhost:3000/#/auth/callback`

Put credentials in `backend/.env`:

- `SPOTIFY_CLIENT_ID`
- `SPOTIFY_CLIENT_SECRET`

## 3) Frontend (Flutter Web)

```powershell
cd emotune\frontend
flutter create --platforms=web .
flutter pub get
flutter run -d chrome --web-port 3000 --dart-define=API_BASE=http://127.0.0.1:8000/api
```

## 4) Admin (Web)

- Django admin login: `http://127.0.0.1:8000/admin/`
- Dashboard API: `http://127.0.0.1:8000/api/admin/dashboard/`
- Accounts API: `http://127.0.0.1:8000/api/admin/accounts/`

Use superuser credentials to access admin APIs.
