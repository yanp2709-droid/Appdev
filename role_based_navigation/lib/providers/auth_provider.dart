import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/user_model.dart';

class AuthProvider with ChangeNotifier {
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  UserModel? _user;

  UserModel? get user => _user;

  Future<void> saveUser(UserModel user) async {
    _user = user;
    await _storage.write(key: 'email', value: user.email);
    await _storage.write(key: 'role', value: user.role);
    notifyListeners();
  }

  Future<void> loadUser() async {
    String? email = await _storage.read(key: 'email');
    String? role = await _storage.read(key: 'role');

    if (email != null && role != null) {
      _user = UserModel(email: email, role: role);
    }
    notifyListeners();
  }

  Future<void> logout() async {
    _user = null;
    await _storage.deleteAll();
    notifyListeners();
  }
}