import 'package:flutter/material.dart';
import '../features/auth/login_screen.dart';
import '../features/auth/student_home_screen.dart';
import '../features/auth/admin_home_screen.dart';

class AppRouter {
  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case '/login':
        return MaterialPageRoute(builder: (_) => const LoginScreen());
      case '/student':
        return MaterialPageRoute(builder: (_) => const StudentHomeScreen());
      case '/admin':
        return MaterialPageRoute(builder: (_) => const AdminHomeScreen());
      default:
        return MaterialPageRoute(
          builder: (_) => const Scaffold(
            body: Center(child: Text("Route not found")),
          ),
        );
    }
  }
}