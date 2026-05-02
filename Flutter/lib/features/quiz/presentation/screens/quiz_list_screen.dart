import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/app_widgets.dart';
import '../../providers/quiz_list_provider.dart';
import '../../providers/quiz_provider.dart';
import '../../data/models/quiz.dart';

/// Screen showing list of quizzes for a selected subject
/// Navigation: Subject -> QuizListScreen -> QuizScreen
class QuizListScreen extends StatefulWidget {
  final int subjectId;
  final String subjectName;

  const QuizListScreen({
    super.key,
    required this.subjectId,
    required this.subjectName,
  });

  @override
  State<QuizListScreen> createState() => _QuizListScreenState();
}

class _QuizListScreenState extends State<QuizListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<QuizListProvider>().fetchQuizzes(
        widget.subjectId,
        subjectName: widget.subjectName,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.subjectName),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/categories'),
        ),
      ),
      body: Consumer<QuizListProvider>(
        builder: (context, provider, _) {
          switch (provider.status) {
            case QuizzesStatus.initial:
            case QuizzesStatus.loading:
              return _buildLoadingView();
            case QuizzesStatus.error:
              return _buildErrorView(provider);
            case QuizzesStatus.empty:
              return _buildEmptyView();
            case QuizzesStatus.success:
              return _buildQuizListView(provider);
          }
        },
      ),
    );
  }

  /// Loading view with shimmer effect
  Widget _buildLoadingView() {
    return const Padding(
      padding: EdgeInsets.all(16),
      child: Column(
        children: [
          _ShimmerCard(),
          SizedBox(height: 12),
          _ShimmerCard(),
          SizedBox(height: 12),
          _ShimmerCard(),
        ],
      ),
    );
  }

  /// Error view with retry button
  Widget _buildErrorView(QuizListProvider provider) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            ErrorBanner(
              message: provider.errorMessage ?? 'Failed to load quizzes.',
              onRetry: () => provider.refresh(),
            ),
          ],
        ),
      ),
    );
  }

  /// Empty state - no quizzes available for this subject
  Widget _buildEmptyView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                color: AppColors.gray100,
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Center(
                child: Text(
                  '📝',
                  style: TextStyle(fontSize: 48),
                ),
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'No quizzes available',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w700,
                color: AppColors.textDark,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              'There are no quizzes available for ${widget.subjectName} yet.\nCheck back later!',
              style: const TextStyle(
                fontSize: 15,
                color: AppColors.gray600,
                height: 1.5,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: () => context.go('/categories'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.arrow_back, color: Colors.white),
                    SizedBox(width: 8),
                    Text(
                      'Back to Categories',
                      style: TextStyle(
                        fontSize: 16,
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

  /// Quiz list view
  Widget _buildQuizListView(QuizListProvider provider) {
    final quizzes = provider.activeQuizzes;

    if (quizzes.isEmpty) {
      return _buildEmptyView();
    }

    return RefreshIndicator(
      onRefresh: () async => provider.refresh(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: quizzes.length,
        itemBuilder: (context, index) {
          final quiz = quizzes[index];
          return _QuizCard(
            quiz: quiz,
            onTap: () => _handleQuizTap(quiz),
            subjectName: widget.subjectName,
          );
        },
      ),
    );
  }

  /// Handle quiz tap - navigate to quiz screen
  Future<void> _handleQuizTap(QuizModel quiz) async {
    final quizListProvider = context.read<QuizListProvider>();
    final quizProvider = context.read<QuizProvider>();
    // Select the quiz and start quiz attempt
    quizListProvider.selectQuiz(quiz);
    final attemptType = await _chooseAttemptType(quiz);
    if (attemptType == null) return;

    await quizProvider.startQuizWithQuiz(
      quizId: quiz.id,
      categoryId: widget.subjectId,
      categoryName: widget.subjectName,
      attemptType: attemptType,
    );

    if (mounted) {
      final qp = context.read<QuizProvider>();
      if (qp.status == QuizStatus.active) {
        context.go('/quiz');
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(qp.errorMessage ?? 'Failed to load quiz'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<String?> _chooseAttemptType(QuizModel quiz) async {
    final quizProvider = context.read<QuizProvider>();
    await quizProvider.refreshAttemptAvailabilityByQuiz(quiz.id);
    if (!mounted) return null;

    final availability = quizProvider.attemptAvailability;
    final gradedAvailable = availability.gradedAttemptAvailable;

    return showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                quiz.title,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textDark,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                gradedAvailable
                    ? 'Select how you want to take this quiz.'
                    : 'Graded attempt has already been used. Practice mode is still available.',
                style: const TextStyle(color: AppColors.gray600),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: gradedAvailable
                    ? () => Navigator.pop(sheetContext, 'graded')
                    : null,
                child: const Text('Start Graded Quiz'),
              ),
              const SizedBox(height: 10),
              OutlinedButton(
                onPressed: () => Navigator.pop(sheetContext, 'practice'),
                child: const Text('Practice Mode'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Quiz card widget showing quiz details
class _QuizCard extends StatelessWidget {
  final QuizModel quiz;
  final VoidCallback onTap;
  final String subjectName;

  const _QuizCard({
    required this.quiz,
    required this.onTap,
    required this.subjectName,
  });

  @override
  Widget build(BuildContext context) {
    final isAvailable = quiz.isActive;

    return GestureDetector(
      onTap: isAvailable ? onTap : null,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isAvailable ? Colors.white : AppColors.gray100,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isAvailable
                ? AppColors.primary.withValues(alpha: 0.3)
                : AppColors.gray200,
            width: 1,
          ),
          boxShadow: isAvailable
              ? [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Row(
          children: [
            // Quiz icon
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: isAvailable
                    ? AppColors.primary.withValues(alpha: 0.1)
                    : AppColors.gray200,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Icon(
                  Icons.quiz_outlined,
                  color: isAvailable ? AppColors.primary : AppColors.gray400,
                  size: 28,
                ),
              ),
            ),
            const SizedBox(width: 16),

            // Quiz info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          quiz.title,
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: isAvailable ? AppColors.textDark : AppColors.gray400,
                          ),
                        ),
                      ),
                      if (!isAvailable)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.gray200,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Text(
                            'Unavailable',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: AppColors.gray600,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  if (quiz.description != null && quiz.description!.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 4),
                      child: Text(
                        quiz.description!,
                        style: TextStyle(
                          fontSize: 13,
                          color: isAvailable ? AppColors.gray600 : AppColors.gray400,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  Row(
                    children: [
                      _InfoChip(
                        icon: Icons.quiz_outlined,
                        label: quiz.questionCountDisplay,
                        color: isAvailable ? AppColors.primary : AppColors.gray400,
                      ),
                      const SizedBox(width: 12),
                      _InfoChip(
                        icon: Icons.timer_outlined,
                        label: quiz.timeLimitDisplay,
                        color: isAvailable ? AppColors.accent : AppColors.gray400,
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Arrow
            if (isAvailable)
              const Icon(
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

/// Small info chip showing quiz metadata
class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;

  const _InfoChip({
    required this.icon,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: color,
          ),
        ),
      ],
    );
  }
}

/// Shimmer placeholder for loading state
class _ShimmerCard extends StatelessWidget {
  const _ShimmerCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 100,
      decoration: BoxDecoration(
        color: AppColors.gray200,
        borderRadius: BorderRadius.circular(14),
      ),
    );
  }
}
