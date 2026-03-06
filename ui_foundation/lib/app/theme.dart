import 'package:flutter/material.dart';

// Define app colors
class AppColors {
  static const primary = Color(0xFF4A90E2);
  static const secondary = Color(0xFF50E3C2);
  static const error = Color(0xFFE74C3C);
  static const background = Color(0xFFF5F5F5);
}

// Define app text styles
class AppTextStyles {
  static const heading = TextStyle(
    fontSize: 24,
    fontWeight: FontWeight.bold,
    color: Colors.black,
  );

  static const body = TextStyle(
    fontSize: 16,
    color: Colors.black87,
  );

  static const label = TextStyle(
    fontSize: 14,
    color: Colors.black54,
  );
}

final ThemeData appTheme = ThemeData(
  primaryColor: AppColors.primary,
  scaffoldBackgroundColor: AppColors.background,
  appBarTheme: const AppBarTheme(
    backgroundColor: AppColors.primary,
    foregroundColor: Colors.white,
  ),
  inputDecorationTheme: InputDecorationTheme(
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(8),
    ),
    filled: true,
    fillColor: Colors.white,
    errorStyle: const TextStyle(color: AppColors.error),
  ),
  elevatedButtonTheme: ElevatedButtonThemeData(
    style: ElevatedButton.styleFrom(
      backgroundColor: AppColors.primary,
      foregroundColor: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
      ),
      padding: const EdgeInsets.symmetric(vertical: 14),
    ),
  ),
);