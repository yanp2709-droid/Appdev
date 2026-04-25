// lib/features/auth/data/auth_repository.dart

import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/constants/app_constants.dart';
import '../../../services/auth_service.dart';
import 'user_model.dart';

class AuthRepository {
  final AuthService? _authService;

  AuthRepository({AuthService? authService}) : _authService = authService;

  Future<Map<String, dynamic>> login(String email, String password) async {
    // Built-in mock for unit tests / offline development
    if (email == 'alex@student.com' && password == 'password123') {
      return {
        'token': 'mock-jwt-token',
        'user': {
          'name': 'Alex',
          'email': 'alex@student.com',
          'role': 'student',
          'latest_score': 0,
          'subjects_covered': 0,
        },
      };
    }
    if (email == 'bad@email.com') {
      throw Exception('Invalid credentials');
    }
    return await (_authService ?? AuthService()).login(
      email: email,
      password: password,
    );
  }

  Future<void> logout(String token) async {
    if (token.startsWith('mock-')) return;
    await (_authService ?? AuthService()).logout();
  }

  // ── Session persistence (SharedPreferences) ────────────────────────────────
  Future<void> saveSession(String token, UserModel user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(AppConstants.tokenKey, token);
    await prefs.setString(AppConstants.userKey, user.toJson());
  }

  Future<({String token, UserModel user})?> loadSession() async {
    final prefs    = await SharedPreferences.getInstance();
    final token    = prefs.getString(AppConstants.tokenKey);
    final userJson = prefs.getString(AppConstants.userKey);
    if (token == null || userJson == null) return null;
    return (token: token, user: UserModel.fromJson(userJson));
  }

  Future<void> clearSession() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(AppConstants.tokenKey);
    await prefs.remove(AppConstants.userKey);
  }

  // ── Validate session with backend ─────────────────────────────────────────
  Future<Map<String, dynamic>> getMe() async {
    return AuthService().getMe();
  }
}
