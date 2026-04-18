import 'package:flutter/foundation.dart';

/// API Configuration
class ApiConfig {
  // Base URL for API requests.
  // Use the dart-define value first if provided.
  // Local defaults vary by runtime so the same project works on
  // Flutter web, Android emulator, and desktop without manual edits.
  static final String baseUrl = _resolveBaseUrl();

  static String _resolveBaseUrl() {
    const envUrl = String.fromEnvironment('API_BASE_URL');
    if (envUrl.isNotEmpty) return envUrl;
    if (kIsWeb) return 'http://127.0.0.1:8001/api';

    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return 'http://10.0.2.2:8001/api';
      case TargetPlatform.iOS:
      case TargetPlatform.macOS:
      case TargetPlatform.windows:
      case TargetPlatform.linux:
      case TargetPlatform.fuchsia:
        return 'http://127.0.0.1:8001/api';
    }
  }

  // Timeout durations
  static const Duration connectTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 90);

  // Common headers
  static const Map<String, String> defaultHeaders = {
    "Accept": "application/json",
    "Content-Type": "application/json",
  };
}
