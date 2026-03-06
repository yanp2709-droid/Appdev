import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class AuthProvider with ChangeNotifier {
  final _storage = const FlutterSecureStorage();
  String? _token;
  String? _role;

  String? get token => _token;
  String? get role => _role;
  bool get isAuthenticated => _token != null;

  // Load stored token and role
  Future<void> loadUser() async {
    _token = await _storage.read(key: 'token');
    _role = await _storage.read(key: 'role');
    notifyListeners();
  }

  // Login function
  Future<bool> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('https://your-api.com/login'), // replace with your API
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      _token = data['token'];
      _role = data['role']; // 'student' or 'admin'
      await _storage.write(key: 'token', value: _token);
      await _storage.write(key: 'role', value: _role);
      notifyListeners();
      return true;
    } else {
      return false;
    }
  }

  // Logout function
  Future<void> logout() async {
    try {
      await http.post(
        Uri.parse('https://your-api.com/logout'),
        headers: {'Authorization': 'Bearer $_token'},
      );
    } catch (_) {} // ignore errors
    _token = null;
    _role = null;
    await _storage.delete(key: 'token');
    await _storage.delete(key: 'role');
    notifyListeners();
  }
}