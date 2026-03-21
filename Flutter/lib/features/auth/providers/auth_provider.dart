import 'package:flutter/foundation.dart';
import '../data/auth_repository.dart';
import '../data/user_model.dart';

enum AuthStatus { initial, loading, authenticated, unauthenticated, error }

class AuthProvider extends ChangeNotifier {
  final AuthRepository _repo;

  AuthStatus _status = AuthStatus.initial;
  UserModel? _user;
  String? _token;
  String? _errorMessage;

  AuthProvider(this._repo);

  AuthStatus get status => _status;
  UserModel? get user => _user;
  String? get token => _token;
  String? get errorMessage => _errorMessage;
  bool get isLoggedIn => _status == AuthStatus.authenticated;

  // ── Called once on app start ───────────────────────────────────────────────
  Future<void> init() async {
    _status = AuthStatus.loading;
    notifyListeners();

    final session = await _repo.loadSession();
    if (session != null) {
      _token = session.token;
      _user = session.user;
      try {
        // Validate session with backend
        final me = await _repo.getMe();
        _user = UserModel.fromMap(me);
        _status = AuthStatus.authenticated;
      } catch (e) {
        // If backend returns 401 or user not found, clear session
        await _repo.clearSession();
        _user = null;
        _token = null;
        _status = AuthStatus.unauthenticated;
      }
    } else {
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  // ── Login ──────────────────────────────────────────────────────────────────
  Future<bool> login(String email, String password) async {
    _status = AuthStatus.loading;
    _errorMessage = null;
    notifyListeners();

    try {
      final data = await _repo.login(email, password);
      final token = data['token'];
      final user = data['user'];
      if (token == null || user == null) {
        throw Exception('Login failed: invalid response from server');
      }
      _token = token as String;
      _user = UserModel.fromMap(user as Map<String, dynamic>);
      await _repo.saveSession(_token!, _user!);
      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _status = AuthStatus.error;
      notifyListeners();
      return false;
    }
  }

  // ── Logout ─────────────────────────────────────────────────────────────────
  Future<void> logout() async {
    _status = AuthStatus.loading;
    notifyListeners();

    if (_token != null) await _repo.logout(_token!);
    await _repo.clearSession();
    _user = null;
    _token = null;
    _errorMessage = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }
}
