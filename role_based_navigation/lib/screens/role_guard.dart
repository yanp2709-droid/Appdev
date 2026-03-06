// lib/screens/role_guard.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import 'login_screen.dart'; // we need this for redirect if not logged in

class RoleGuard extends StatelessWidget {
  final Widget studentScreen;
  final Widget adminScreen;

  const RoleGuard({
    super.key,
    required this.studentScreen,
    required this.adminScreen,
  });

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);

    // If not logged in, redirect to LoginScreen
    if (auth.user == null) {
      return const LoginScreen();
    }

    // Show screens based on role
    if (auth.user!.role == 'student') {
      return studentScreen;
    } else {
      return adminScreen;
    }
  }
}