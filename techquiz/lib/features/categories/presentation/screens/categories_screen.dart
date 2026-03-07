// lib/features/categories/presentation/screens/categories_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/app_widgets.dart';
import '../../../auth/providers/auth_provider.dart';
import '../../../home/admin/admin_home_screen.dart';
import '../../../quiz/providers/quiz_provider.dart';
import '../../../admin/providers/admin_questions_provider.dart';
import '../../../admin/presentation/screens/admin_questions_screen.dart';
import '../../data/categories_repository.dart';
import '../../data/category_model.dart';
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

  @override
  Widget build(BuildContext context) {
    final isAdmin   = context.watch<AuthProvider>().user?.role == 'admin';
    final backRoute = isAdmin ? '/admin-home' : '/student-home';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Categories'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go(backRoute),
        ),
        // Task 3: test buttons for simulating API states (admin + student)
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.bug_report, color: Colors.white),
            tooltip: 'Simulate API state',
            onSelected: (val) {
              if (val == 'error') _simulateError();
              if (val == 'empty') _simulateEmpty();
              if (val == 'normal') _load();
            },
            itemBuilder: (_) => const [
              PopupMenuItem(value: 'normal', child: Text('✅ Normal')),
              PopupMenuItem(value: 'error',  child: Text('❌ Simulate Error')),
              PopupMenuItem(value: 'empty',  child: Text('📭 Simulate Empty')),
            ],
          ),
        ],
      ),
      drawer: isAdmin
          ? const AdminDrawerWidget(currentRoute: '/categories')
          : null,
      body: Consumer<CategoriesProvider>(
        builder: (context, provider, _) {
          switch (provider.status) {
            // ── Loading ──────────────────────────────────────────────────
            case CategoriesStatus.initial:
            case CategoriesStatus.loading:
              return Column(children: [
                _ShimmerList(),
                Container(
                  margin: const EdgeInsets.symmetric(horizontal: 16),
                  width: double.infinity, height: 50,
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Center(
                    child: Text('Loading...',
                        style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                            fontSize: 15)),
                  ),
                ),
              ]);

            // ── Error (Task 3) ────────────────────────────────────────────
            case CategoriesStatus.error:
              return Column(children: [
                ErrorBanner(
                  message: 'Failed to load categories.',
                  onRetry: _load,
                ),
                _ShimmerList(),
              ]);

            // ── Empty (Task 3) ────────────────────────────────────────────
            case CategoriesStatus.empty:
              return const EmptyState(
                emoji: '📦',
                title: 'No categories found',
                subtitle: "You don't have permission\nto view this page.",
              );

            // ── Success ───────────────────────────────────────────────────
            case CategoriesStatus.success:
              return ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: provider.categories.length,
                itemBuilder: (_, i) {
                  final cat = provider.categories[i];
                  return _CategoryCard(
                    category: cat,
                    isAdmin: isAdmin,
                    onTap: () {
                      if (isAdmin) {
                        // Admin → manage questions for this category
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => ChangeNotifierProvider.value(
                              value: context.read<AdminQuestionsProvider>(),
                              child: AdminQuestionsScreen(category: cat),
                            ),
                          ),
                        );
                      } else {
                        // Student → start quiz (Task 3: navigate to quiz screen)
                        context.read<QuizProvider>().startQuiz(
                            cat.id, cat.name);
                        context.go('/quiz');
                      }
                    },
                  );
                },
              );
          }
        },
      ),
      bottomNavigationBar: !isAdmin
          ? BottomNavigationBar(
              items: const [
                BottomNavigationBarItem(
                    icon: Icon(Icons.home), label: ''),
                BottomNavigationBarItem(
                    icon: Icon(Icons.person), label: ''),
                BottomNavigationBarItem(
                    icon: Icon(Icons.notifications), label: ''),
              ],
              selectedItemColor: AppColors.primary,
              unselectedItemColor: AppColors.gray400,
              showSelectedLabels: false,
              showUnselectedLabels: false,
              currentIndex: 0,
            )
          : null,
    );
  }
}

// ── Category card ─────────────────────────────────────────────────────────────
class _CategoryCard extends StatelessWidget {
  final CategoryModel category;
  final bool isAdmin;
  final VoidCallback onTap;

  const _CategoryCard({
    required this.category,
    required this.isAdmin,
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
          border: Border.all(color: AppColors.gray200),
          boxShadow: [
            BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 8,
                offset: const Offset(0, 2))
          ],
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(category.name,
                      style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textDark)),
                  const SizedBox(height: 4),
                  Text(category.subtitle,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.gray400)),
                  const SizedBox(height: 6),
                  Text(
                    isAdmin ? 'Tap to manage questions' : 'Tap to start quiz',
                    style: TextStyle(
                      fontSize: 11,
                      color: isAdmin ? AppColors.accent : AppColors.primary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              width: 48, height: 48,
              decoration: BoxDecoration(
                color: category.color.withOpacity(0.18),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                  child: Text(category.emoji,
                      style: const TextStyle(fontSize: 24))),
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
