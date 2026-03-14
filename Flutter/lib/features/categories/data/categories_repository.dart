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

  CategoriesRepository({CategoriesService? categoriesService})
      : _categoriesService = categoriesService ?? CategoriesService();

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

    // Fetch only from real API (no fallback)
    final categories = await _categoriesService.getCategories();
    return categories;
  }
}
