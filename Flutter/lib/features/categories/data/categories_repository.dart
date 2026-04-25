// lib/features/categories/data/categories_repository.dart
// Fetches real categories from Laravel API

import '../../../services/categories_service.dart';
import '../../../core/errors/app_exceptions.dart';
import './models/category.dart';

enum SimulateState { normal, error, empty }

class CategoriesRepository {
  // For testing/development only - can be set to simulate different states
  static SimulateState simulateState = SimulateState.normal;

  final CategoriesService _categoriesService;
  final bool _useMockData;

  CategoriesRepository({CategoriesService? categoriesService})
      : _categoriesService = categoriesService ?? CategoriesService(),
        _useMockData = categoriesService == null;

  /// Fetch published categories from Laravel API (database only)
  ///
  /// Returns: List of CategoryModel objects from database
  /// Throws: NetworkException on network/API error
  Future<List<CategoryModel>> fetchCategories() async {
    // For testing UI states (can be removed in production)
    if (simulateState == SimulateState.error) {
      throw const NetworkException();
    }
    if (simulateState == SimulateState.empty) {
      return <CategoryModel>[];
    }
    if (_useMockData) {
      return [
        const CategoryModel(id: 1, name: 'Mathematics', description: 'Math quizzes'),
        const CategoryModel(id: 2, name: 'Science', description: 'Science quizzes'),
        const CategoryModel(id: 3, name: 'History', description: 'History quizzes'),
      ];
    }

    // Fetch from real API
    return await _categoriesService.getCategories();
  }
}
