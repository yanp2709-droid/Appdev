// lib/features/categories/data/categories_repository.dart
// Frontend-only. Simulates GET /categories.
// When a real backend is ready, replace fetchCategories() body with
// an http.get call to: ${AppEnv.baseUrl}${AppEnv.categoriesEndpoint}

import 'package:flutter/material.dart';
import '../../../core/errors/app_exceptions.dart';
import '../../../core/utils/app_env.dart';
import 'category_model.dart';

enum SimulateState { normal, error, empty }

class CategoriesRepository {
  // Set this to test different UI states (Task 3 requirement)
  static SimulateState simulateState = SimulateState.normal;

  static final List<CategoryModel> _categories = [
    CategoryModel(
      id: 1, name: 'Mathematics',
      subtitle: 'Categories · Interior categories',
      color: const Color(0xFF60A5FA), emoji: '🔢',
      questions: 10, minutes: 15,
    ),
    CategoryModel(
      id: 2, name: 'Science',
      subtitle: 'Categories · Interior categories',
      color: const Color(0xFFF87171), emoji: '🔬',
      questions: 12, minutes: 18,
    ),
    CategoryModel(
      id: 3, name: 'History',
      subtitle: 'Categories · Interior categories',
      color: const Color(0xFFFBBF24), emoji: '📜',
      questions: 8, minutes: 12,
    ),
    CategoryModel(
      id: 4, name: 'Technology',
      subtitle: 'Categories · Interior categories',
      color: const Color(0xFF34D399), emoji: '💻',
      questions: 15, minutes: 20,
    ),
  ];

  // Simulates GET /categories
  // Endpoint would be: ${AppEnv.baseUrl}${AppEnv.categoriesEndpoint}
  Future<List<CategoryModel>> fetchCategories() async {
    // Simulate network delay
    await Future.delayed(const Duration(milliseconds: 800));

    // Task 3: simulate API down → error state
    if (simulateState == SimulateState.error) {
      throw const NetworkException();
    }

    // Task 3: simulate no categories → empty state
    if (simulateState == SimulateState.empty) {
      return [];
    }

    return _categories;
  }
}
