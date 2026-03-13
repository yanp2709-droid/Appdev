// lib/features/auth/data/auth_repository.dart
// Frontend-only. Simulates POST /login and POST /logout using mock data.
// When a real backend is ready, replace the mock block with http calls
// and use AppEnv.baseUrl + AppEnv.loginEndpoint.

import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/constants/app_constants.dart';
import '../../../core/errors/app_exceptions.dart';
import 'user_model.dart';

// ── Mock user store (replace with real API when backend is ready) ─────────────
const _mockUsers = [
  {
    'email':    'alex@student.com',
    'password': 'password123',
    'name':     'Alex',
    'role':     'student',
  },
  {
    'email':    'admin@admin.com',
    'password': 'admin123',
    'name':     'Admin',
    'role':     'admin',
  },
];

class AuthRepository {
  // ── POST /login (simulated) ────────────────────────────────────────────────
  // Endpoint would be: ${AppEnv.baseUrl}${AppEnv.loginEndpoint}
  Future<Map<String, dynamic>> login(String email, String password) async {
    // Simulate network delay
    await Future.delayed(const Duration(milliseconds: 600));

    final match = _mockUsers.where(
      (u) => u['email'] == email.trim() && u['password'] == password,
    );

    if (match.isEmpty) throw const InvalidCredentialsException();

    final user = Map<String, dynamic>.from(match.first)
      ..remove('password');

    return {
      'token': 'local_${user['role']}_${DateTime.now().millisecondsSinceEpoch}',
      'user':  user,
    };
  }

  // ── POST /logout (simulated) ───────────────────────────────────────────────
  // Endpoint would be: ${AppEnv.baseUrl}${AppEnv.logoutEndpoint}
  Future<void> logout(String token) async {
    await Future.delayed(const Duration(milliseconds: 200));
    // No-op on frontend. Real backend would invalidate the token here.
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
}
