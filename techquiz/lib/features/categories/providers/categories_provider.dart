// lib/features/categories/providers/categories_provider.dart
import 'package:flutter/foundation.dart';
import '../data/categories_repository.dart';
import '../data/category_model.dart';

enum CategoriesStatus { initial, loading, success, error, empty }

class CategoriesProvider extends ChangeNotifier {
  final CategoriesRepository _repo;

  CategoriesStatus      _status     = CategoriesStatus.initial;
  List<CategoryModel>   _categories = [];
  String?               _errorMessage;

  CategoriesProvider(this._repo);

  CategoriesStatus    get status       => _status;
  List<CategoryModel> get categories   => List.unmodifiable(_categories);
  String?             get errorMessage => _errorMessage;

  Future<void> fetch() async {
    _status       = CategoriesStatus.loading;
    _errorMessage = null;
    notifyListeners();

    try {
      final data  = await _repo.fetchCategories();
      _categories = data;
      _status     = data.isEmpty ? CategoriesStatus.empty : CategoriesStatus.success;
    } catch (e) {
      _errorMessage = e.toString();
      _status       = CategoriesStatus.error;
    }
    notifyListeners();
  }
}
