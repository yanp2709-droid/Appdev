import 'package:flutter/material.dart';
import '../models/category_model.dart';
import '../repository/categories_repository.dart';

class CategoriesProvider with ChangeNotifier {

  final CategoriesRepository _repository = CategoriesRepository();

  List<Category> _categories = [];
  bool _isLoading = false;
  String? _error;

  List<Category> get categories => _categories;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadCategories() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _categories = await _repository.fetchCategories();
    } catch (e) {
      _error = "Failed to load categories";
    }

    _isLoading = false;
    notifyListeners();
  }
}