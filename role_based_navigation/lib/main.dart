import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'screens/login_screen.dart';
import 'screens/student_home_screen.dart';
import 'screens/admin_home_screen.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthProvider()..loadUser(),
      child: MaterialApp(
        title: 'Role-Based Navigation',
        initialRoute: '/login',
        routes: {
          '/login': (_) => const LoginScreen(),
          '/student': (_) => const StudentHomeScreen(),
          '/admin': (_) => const AdminHomeScreen(),
        },
      ),
    );
  }
}