// lib/core/utils/app_env.dart
// Environment configuration - kept in sync with ApiConfig.

import '../config/api_config.dart';

class AppEnv {
  AppEnv._();

  // If you need separate prod/dev later, update these.
  // For now, use the same base URL as ApiConfig.
  static String get baseUrl => ApiConfig.baseUrl;

  // Endpoints
  static const String loginEndpoint      = '/login';
  static const String logoutEndpoint     = '/logout';
  static const String categoriesEndpoint = '/categories';
}
