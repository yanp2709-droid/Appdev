import '../models/category_model.dart';

class CategoriesRepository {
  Future<List<Category>> fetchCategories() async {

    await Future.delayed(const Duration(seconds: 2));

    return [
      Category(id: 1, name: "Science"),
      Category(id: 2, name: "Mathematics"),
      Category(id: 3, name: "Programming"),
    ];
  }
}