// lib/main.dart
import 'package:flutter/material.dart';
import 'app/theme.dart';
import 'features/auth/login_screen.dart';

void main() {
  runApp(const TechQuizApp());
}

class TechQuizApp extends StatelessWidget {
  const TechQuizApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'TechQuiz UI Foundation',
      debugShowCheckedModeBanner: false,
      theme: appTheme,           // Apply your custom theme
      home: const LoginScreen(), // Start with the LoginScreen
    );
  }
}