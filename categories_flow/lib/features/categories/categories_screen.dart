import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'providers/categories_provider.dart';
import 'models/category_model.dart';
import 'questions_placeholder_screen.dart';

import 'loading_view.dart';
import 'error_view.dart';
import 'empty_state.dart';

class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {

  @override
  void initState() {
    super.initState();

    Future.microtask(() {
      Provider.of<CategoriesProvider>(context, listen: false)
          .loadCategories();
    });
  }

  @override
  Widget build(BuildContext context) {

    final provider = Provider.of<CategoriesProvider>(context);

    if (provider.isLoading) {
      return const LoadingView();
    }

    if (provider.error != null) {
      return ErrorView(
        message: provider.error!,
        onRetry: () {
          provider.loadCategories();
        },
      );
    }

    if (provider.categories.isEmpty) {
      return const EmptyState(message: "No categories found");
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text("Quiz Categories"),
      ),
      body: ListView.builder(
        itemCount: provider.categories.length,
        itemBuilder: (context, index) {

          Category category = provider.categories[index];

          return ListTile(
            title: Text(category.name),
            trailing: const Icon(Icons.arrow_forward),

            onTap: () {

              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => QuestionsPlaceholderScreen(
                    categoryName: category.name,
                  ),
                ),
              );

            },
          );
        },
      ),
    );
  }
}