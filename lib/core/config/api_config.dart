/// API Configuration
class ApiConfig {
  // Base URL for API requests
  // Change this based on your setup:
  // - Android Emulator to host: http://10.0.2.2:8001/api (default)
  // - Android Emulator to specific IP: http://YOUR_IP:8001/api
  // - Physical device: http://YOUR_LAN_IP:8001/api
  static const String baseUrl = "http://10.0.2.2:8001/api";
  
  // Timeout durations
  static const Duration connectTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 60);
  
  // Common headers
  static const Map<String, String> defaultHeaders = {
    "Accept": "application/json",
    "Content-Type": "application/json",
  };
}
