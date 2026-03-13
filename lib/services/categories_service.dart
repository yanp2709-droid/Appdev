import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/exceptions/api_exception.dart';
import '../features/categories/data/models/category.dart';

/// Service for fetching categories from Laravel API
class CategoriesService {
  final ApiClient apiClient = ApiClient();

  /// Fetch all published categories from the database
  /// 
  /// Returns: List<CategoryModel> of published categories
  /// Throws: ApiException on failure
  Future<List<CategoryModel>> getCategories() async {
    try {
      final response = await apiClient.dio.get('/categories');
      
      // Handle both array response and object with data field
      List<dynamic> categoriesData;
      
      if (response.data is List) {
        categoriesData = response.data as List<dynamic>;
      } else if (response.data is Map<String, dynamic>) {
        final data = response.data as Map<String, dynamic>;
        categoriesData = data['data'] as List<dynamic>? ?? [];
      } else {
        throw ApiException(
          message: 'Unexpected response format',
          type: 'parse_error',
        );
      }

      return categoriesData
          .map((json) => CategoryModel.fromJson(json as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
