// lib/features/auth/data/auth_repository.dart

import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/constants/app_constants.dart';
import '../../../services/auth_service.dart';
import 'user_model.dart';

class AuthRepository {
  Future<Map<String, dynamic>> login(String email, String password) async {
    return AuthService().login(email: email, password: password);
  }

  Future<void> logout(String token) async {
    await AuthService().logout();
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
