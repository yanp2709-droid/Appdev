// lib/features/quiz/presentation/screens/quiz_result_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../providers/quiz_provider.dart';
import 'attempt_detail_screen.dart';

class QuizResultScreen extends StatelessWidget {
  const QuizResultScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final result = context.read<QuizProvider>().lastResult;

    if (result == null) {
      WidgetsBinding.instance.addPostFrameCallback(
          (_) => context.go('/student-home'));
      return const Scaffold(body: SizedBox.shrink());
    }

    final score     = result.scorePercent;
    final isPassing = score >= 50;
    final submittedLabel = DateFormat('MMM dd, yyyy hh:mm a').format(result.submittedAt);

    return Scaffold(
      backgroundColor: AppColors.gray100,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Emoji result
              Text(
                score >= 80 ? '🏆' : score >= 50 ? '👍' : '😔',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 80),
              ),
              const SizedBox(height: 16),

              // Score circle
              Center(
                child: Container(
                  width: 140, height: 140,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white,
                    boxShadow: [
                      BoxShadow(
                        color: (isPassing ? AppColors.primary : AppColors.danger)
                            .withOpacity(0.2),
                        blurRadius: 24,
                        spreadRadius: 4,
                      ),
                    ],
                  ),
                  child: Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          '$score%',
                          style: TextStyle(
                            fontSize: 36,
                            fontWeight: FontWeight.w900,
                            color: isPassing
                                ? AppColors.primary
                                : AppColors.danger,
                          ),
                        ),
                        Text(
                          isPassing ? 'Passed!' : 'Failed',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: isPassing
                                ? AppColors.primary
                                : AppColors.danger,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Category name
              Text(
                result.categoryName,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textDark,
                ),
              ),
              const SizedBox(height: 8),

              Text(
                'Submitted: $submittedLabel',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                  color: AppColors.gray600,
                ),
              ),
              const SizedBox(height: 24),

              // Stats row
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  _StatChip(
                    label: 'Correct',
                    value: '${result.correctAnswers}',
                    color: Colors.green,
                  ),
                  const SizedBox(width: 12),
                  _StatChip(
                    label: 'Wrong',
                    value: '${result.totalQuestions - result.correctAnswers}',
                    color: AppColors.danger,
                  ),
                  const SizedBox(width: 12),
                  _StatChip(
                    label: 'Total',
                    value: '${result.totalQuestions}',
                    color: AppColors.gray600,
                  ),
                ],
              ),
              const SizedBox(height: 40),

              ElevatedButton(
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => AttemptDetailScreen(attemptId: result.attemptId),
                    ),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  minimumSize: const Size(double.infinity, 52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: const Text(
                  'Review This Attempt',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
              ),
              const SizedBox(height: 12),

              OutlinedButton(
                onPressed: () => context.go('/history'),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size(double.infinity, 52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: const Text('View Previous Attempts'),
              ),
              const SizedBox(height: 12),

              // Try Again
              ElevatedButton(
                onPressed: () {
                  context.read<QuizProvider>().reset();
                  context.go('/categories');
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.accent,
                  minimumSize: const Size(double.infinity, 52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: const Text(
                  'Try Another Category',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
              ),
              const SizedBox(height: 12),

              // Back to home
              OutlinedButton(
                onPressed: () {
                  context.read<QuizProvider>().reset();
                  context.go('/student-home');
                },
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size(double.infinity, 52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: const Text('Back to Home'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  final String label;
  final String value;
  final Color  color;

  const _StatChip({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w900,
              color: color,
            ),
          ),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}
