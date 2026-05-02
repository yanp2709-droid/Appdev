// lib/features/categories/presentation/screens/categories_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/app_widgets.dart';
import '../../data/categories_repository.dart';
import '../../data/models/category.dart';
import '../../providers/categories_provider.dart';

class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  void _load() {
    CategoriesRepository.simulateState = SimulateState.normal;
    context.read<CategoriesProvider>().fetch();
  }

  void _openQuizList(CategoryModel category) {
    final encodedName = Uri.encodeComponent(category.name);
    context.go('/categories/${category.id}/quizzes?categoryName=$encodedName');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Quiz Categories'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/student-home'),
        ),
      ),
      body: Consumer<CategoriesProvider>(
        builder: (context, provider, _) => _buildCategoryListView(provider),
      ),
    );
  }

  Widget _buildCategoryListView(CategoriesProvider provider) {
    switch (provider.status) {
      case CategoriesStatus.initial:
      case CategoriesStatus.loading:
        return const _ShimmerList();

      case CategoriesStatus.error:
        return ErrorBanner(
          message: provider.errorMessage ?? 'Failed to load categories.',
          onRetry: _load,
        );

      case CategoriesStatus.empty:
        return const EmptyState(
          emoji: '??',
          title: 'No categories found',
          subtitle: "You don't have permission\nto view this page.",
        );

      case CategoriesStatus.success:
        return RefreshIndicator(
          onRefresh: () async => _load(),
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: provider.categories.length,
            itemBuilder: (_, i) {
              final category = provider.categories[i];
              return _CategoryCard(
                category: category,
                onTap: () => _openQuizList(category),
              );
            },
          ),
        );
    }
  }
}

class _CategoryCard extends StatelessWidget {
  final CategoryModel category;
  final VoidCallback onTap;

  const _CategoryCard({
    required this.category,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.gray200, width: 1),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: category.color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Text(category.emoji, style: const TextStyle(fontSize: 28)),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    category.name,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF1F2937),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    category.description,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF6B7280),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios, size: 16, color: AppColors.gray400),
          ],
        ),
      ),
    );
  }
}

class _ShimmerList extends StatelessWidget {
  const _ShimmerList();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: List.generate(
          3,
          (_) => Container(
            height: 80,
            margin: const EdgeInsets.only(bottom: 12),
            decoration: BoxDecoration(
              color: AppColors.gray200,
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),
      ),
    );
  }
}
