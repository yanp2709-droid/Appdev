// lib/core/utils/app_env.dart
// Environment configuration — switch isProduction to true for a real backend.

class AppEnv {
  AppEnv._();

  static const bool _isProduction = false;

  static const String _devBaseUrl  = 'https://dev.api.techquiz.com';
  static const String _prodBaseUrl = 'https://api.techquiz.com';

  static String get baseUrl => _isProduction ? _prodBaseUrl : _devBaseUrl;

  // Endpoints
  static const String loginEndpoint      = '/login';
  static const String logoutEndpoint     = '/logout';
  static const String categoriesEndpoint = '/categories';
}
