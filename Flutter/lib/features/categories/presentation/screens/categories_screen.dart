// lib/features/categories/presentation/screens/categories_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/app_widgets.dart';
import '../../../quiz/providers/quiz_provider.dart';
import '../../data/categories_repository.dart';
import '../../data/models/category.dart';
import '../../providers/categories_provider.dart';

class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key});

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
  CategoryModel? _selectedCategory;
  bool _showCategoryList = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  void _load() {
    // Reset to normal before fetching
    CategoriesRepository.simulateState = SimulateState.normal;
    context.read<CategoriesProvider>().fetch();
  }

  void _simulateError() {
    CategoriesRepository.simulateState = SimulateState.error;
    context.read<CategoriesProvider>().fetch();
  }

  void _simulateEmpty() {
    CategoriesRepository.simulateState = SimulateState.empty;
    context.read<CategoriesProvider>().fetch();
  }

  Future<void> _selectCategory(CategoryModel category) async {
    setState(() {
      _selectedCategory = category;
    });
    await context.read<QuizProvider>().refreshAttemptAvailability(category.id);
  }

  void _showSelectCategoryDialog() {
    setState(() {
      _showCategoryList = true;
      _selectedCategory = null;
    });
  }

  void _resetSelection() {
    setState(() {
      _showCategoryList = false;
      _selectedCategory = null;
    });
  }

  Future<void> _startQuiz(String attemptType) async {
    if (_selectedCategory == null) return;

    await context.read<QuizProvider>().startQuiz(
      _selectedCategory!.id,
      _selectedCategory!.name,
      attemptType: attemptType,
    );

    if (mounted) {
      final quizProvider = context.read<QuizProvider>();
      if (quizProvider.status == QuizStatus.active) {
        context.go('/quiz');
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(quizProvider.errorMessage ?? 'Failed to load quiz'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Quiz'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/student-home'),
        ),
        actions: const [],
      ),
      body: Consumer<CategoriesProvider>(
        builder: (context, provider, _) {
          // If no category selected and not showing list, show "Select Category" button
          if (!_showCategoryList && _selectedCategory == null) {
            return _buildSelectCategoryView();
          }

          // If showing category list
          if (_showCategoryList && _selectedCategory == null) {
            return _buildCategoryListView(provider);
          }

          // If category selected, show confirmation
          if (_selectedCategory != null) {
            return _buildCategoryConfirmationView();
          }

          return const SizedBox.shrink();
        },
      ),
      bottomNavigationBar: null,
    );
  }

  /// Screen 1: Initial "Select Category" button
  Widget _buildSelectCategoryView() {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                color: AppColors.primary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Center(
                child: Text(
                  '📚',
                  style: TextStyle(fontSize: 64),
                ),
              ),
            ),
            const SizedBox(height: 32),
            const Text(
              'Select a Category',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.w700,
                color: Color(0xFF1F2937),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            const Text(
              'Choose a category to start your quiz and test your knowledge!',
              style: TextStyle(
                fontSize: 16,
                color: Color(0xFF6B7280),
                height: 1.5,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 48),
            SizedBox(
              width: double.infinity,
              height: 56,
              child: ElevatedButton(
                onPressed: _showSelectCategoryDialog,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 4,
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.category_outlined, size: 24, color: Colors.white),
                    SizedBox(width: 12),
                    Text(
                      'Select Category',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Screen 2: Category list view
  Widget _buildCategoryListView(CategoriesProvider provider) {
    switch (provider.status) {
      case CategoriesStatus.initial:
      case CategoriesStatus.loading:
        return Column(
          children: [
            _ShimmerList(),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: ElevatedButton.icon(
                onPressed: _resetSelection,
                icon: const Icon(Icons.arrow_back),
                label: const Text('Back'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.gray200,
                ),
              ),
            ),
          ],
        );

      case CategoriesStatus.error:
        return Column(
          children: [
            ErrorBanner(
              message: provider.errorMessage ?? 'Failed to load categories.',
              onRetry: _load,
            ),
            _ShimmerList(),
          ],
        );

      case CategoriesStatus.empty:
        return Column(
          children: [
            const Expanded(
              child: EmptyState(
                emoji: '📦',
                title: 'No categories found',
                subtitle: "You don't have permission\nto view this page.",
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _resetSelection,
                  icon: const Icon(Icons.arrow_back),
                  label: const Text('Back'),
                ),
              ),
            ),
          ],
        );

      case CategoriesStatus.success:
        return Column(
          children: [
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async {
                  await Future.delayed(const Duration(milliseconds: 500));
                  _load();
                },
                child: ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: provider.categories.length,
                  itemBuilder: (_, i) {
                    final cat = provider.categories[i];
                    return _CategoryCard(
                      category: cat,
                      onTap: () => _selectCategory(cat),
                      isSelected: false,
                    );
                  },
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _resetSelection,
                  icon: const Icon(Icons.arrow_back),
                  label: const Text('Back'),
                ),
              ),
            ),
          ],
        );
    }
  }

  /// Screen 3: Category confirmation with "Start Quiz" button
  Widget _buildCategoryConfirmationView() {
    if (_selectedCategory == null) {
      return const SizedBox.shrink();
    }

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                color: _selectedCategory!.color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Center(
                child: Text(
                  _selectedCategory!.emoji,
                  style: const TextStyle(fontSize: 56),
                ),
              ),
            ),
            const SizedBox(height: 32),
            Text(
              _selectedCategory!.name,
              style: const TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.w700,
                color: Color(0xFF1F2937),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              _selectedCategory!.description,
              style: const TextStyle(
                fontSize: 16,
                color: Color(0xFF6B7280),
                height: 1.5,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 48),
            Consumer<QuizProvider>(
              builder: (context, quizProvider, _) {
                final isLoading = quizProvider.status == QuizStatus.loading;
                final isChecking = quizProvider.isCheckingAttemptAvailability;
                final availability = quizProvider.attemptAvailability;
                final gradedAvailable = availability.gradedAttemptAvailable;

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: gradedAvailable
                            ? AppColors.primary.withOpacity(0.08)
                            : Colors.orange.withOpacity(0.10),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: gradedAvailable
                              ? AppColors.primary.withOpacity(0.2)
                              : Colors.orange.withOpacity(0.35),
                        ),
                      ),
                      child: isChecking
                          ? const Row(
                              children: [
                                SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                ),
                                SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    'Checking graded attempt availability...',
                                    style: TextStyle(fontSize: 13),
                                  ),
                                ),
                              ],
                            )
                          : Text(
                              gradedAvailable
                                  ? availability.allowedGradedAttempts > 1
                                      ? 'Your teacher enabled more graded tries. You currently have ${availability.remainingGradedAttempts} graded attempt(s) available.'
                                      : 'Your official graded attempt is still available. Practice mode is always available.'
                                  : 'Your graded attempt has already been used. You may still continue in Practice Mode.',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      height: 56,
                      child: ElevatedButton(
                        onPressed: isLoading || isChecking || !gradedAvailable
                            ? null
                            : () => _startQuiz('graded'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: 4,
                          disabledBackgroundColor: AppColors.gray200,
                        ),
                        child: isLoading
                            ? const SizedBox(
                                height: 24,
                                width: 24,
                                child: CircularProgressIndicator(
                                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                  strokeWidth: 2,
                                ),
                              )
                            : const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.verified_rounded, size: 22, color: Colors.white),
                                  SizedBox(width: 12),
                                  Text(
                                    'Start Graded Quiz',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w600,
                                      color: Colors.white,
                                    ),
                                  ),
                                ],
                              ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      height: 54,
                      child: OutlinedButton.icon(
                        onPressed: isLoading || isChecking
                            ? null
                            : () => _startQuiz('practice'),
                        icon: const Icon(Icons.school_outlined),
                        label: const Text('Practice Mode'),
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: AppColors.accent),
                          foregroundColor: AppColors.accent,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Practice attempts are unlimited and do not affect your official graded result.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 12,
                        color: Color(0xFF6B7280),
                        height: 1.4,
                      ),
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: OutlinedButton.icon(
                onPressed: _resetSelection,
                icon: const Icon(Icons.arrow_back),
                label: const Text('Choose Different Category'),
                style: OutlinedButton.styleFrom(
                  side: BorderSide(color: AppColors.primary),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Category card ─────────────────────────────────────────────────────────────
class _CategoryCard extends StatelessWidget {
  final CategoryModel category;
  final VoidCallback onTap;
  final bool isSelected;

  const _CategoryCard({
    required this.category,
    required this.onTap,
    this.isSelected = false,
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
          border: Border.all(
            color: isSelected ? category.color : AppColors.gray200,
            width: isSelected ? 2 : 1,
          ),
          boxShadow: [
            BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 8,
                offset: const Offset(0, 2))
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: category.color.withOpacity(0.1),
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
            if (isSelected)
              Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  color: category.color,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Center(
                  child: Icon(Icons.check, size: 16, color: Colors.white),
                ),
              )
            else
              Icon(
                Icons.arrow_forward_ios,
                size: 16,
                color: AppColors.gray400,
              ),
          ],
        ),
      ),
    );
  }
}

// ── Shimmer placeholder ───────────────────────────────────────────────────────
class _ShimmerList extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: List.generate(
          3,
          (_) => Container(
            height: 80, margin: const EdgeInsets.only(bottom: 12),
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
