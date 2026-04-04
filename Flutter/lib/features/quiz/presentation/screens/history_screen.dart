// lib/features/quiz/presentation/screens/history_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../data/models/attempt_history.dart';
import '../../data/models/attempt_detail.dart';
import '../../../../services/attempt_history_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final AttemptHistoryService _historyService = AttemptHistoryService();
  late Future<List<AttemptHistoryModel>> _historiesDataFuture;
  final Map<int, Future<AttemptDetailModel>> _detailFutures = {};

  @override
  void initState() {
    super.initState();
    _historiesDataFuture = _historyService.getHistory();
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        if (context.canPop()) {
          return true;
        }
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) context.go('/student-home');
        });
        return false;
      },
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Attempt History'),
          centerTitle: true,
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          automaticallyImplyLeading: true,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () {
              if (context.canPop()) {
                context.pop();
              } else {
                context.go('/student-home');
              }
            },
          ),
        ),
        body: FutureBuilder<List<AttemptHistoryModel>>(
          future: _historiesDataFuture,
          builder: (context, snapshot) {
            // Loading state
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(
                child: CircularProgressIndicator(),
              );
            }

            // Error state
            if (snapshot.hasError) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline, size: 64, color: Colors.red),
                    const SizedBox(height: 16),
                    Text(
                      'Failed to load history',
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      snapshot.error.toString(),
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.grey),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: () => setState(() => _historiesDataFuture = _historyService.getHistory()),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              );
            }

            // Empty state
            if (!snapshot.hasData || snapshot.data!.isEmpty) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.history, size: 64, color: AppColors.primary),
                    const SizedBox(height: 16),
                    Text(
                      'No attempts yet',
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Start taking quizzes to see your history',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.grey),
                    ),
                  ],
                ),
              );
            }

            // Success state
            final attempts = snapshot.data!;
            _primeDetailFutures(attempts);
            final grouped = _groupByCategory(attempts);
            final categoryIds = grouped.keys.toList();
            return ListView.builder(
              padding: const EdgeInsets.all(12),
              itemCount: categoryIds.length,
              itemBuilder: (context, index) {
                final categoryId = categoryIds[index];
                final categoryAttempts = grouped[categoryId] ?? [];
                final categoryName = categoryAttempts.isNotEmpty ? categoryAttempts.first.categoryName : 'Category';
                return _CategorySection(
                  categoryName: categoryName,
                  attempts: categoryAttempts,
                  onAttemptTap: (id) => _navigateToDetail(context, id),
                  detailFutureFor: (id) => _detailFutures[id],
                );
              },
            );
          },
        ),
      ),
    );
  }

  void _navigateToDetail(BuildContext context, int attemptId) {
    context.push('/history/$attemptId');
  }

  void _ensureDetailFuture(int attemptId) {
    _detailFutures.putIfAbsent(
      attemptId,
      () => _historyService.getAttemptDetail(attemptId: attemptId),
    );
  }

  void _primeDetailFutures(List<AttemptHistoryModel> attempts) {
    var added = false;
    for (final attempt in attempts) {
      if (!_detailFutures.containsKey(attempt.id)) {
        _detailFutures[attempt.id] = _historyService.getAttemptDetail(attemptId: attempt.id);
        added = true;
      }
    }
    if (added) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() {});
      });
    }
  }

  Map<int, List<AttemptHistoryModel>> _groupByCategory(List<AttemptHistoryModel> attempts) {
    final Map<int, List<AttemptHistoryModel>> grouped = {};
    for (final attempt in attempts) {
      grouped.putIfAbsent(attempt.categoryId, () => []).add(attempt);
    }
    return grouped;
  }
}

class _CategorySection extends StatelessWidget {
  final String categoryName;
  final List<AttemptHistoryModel> attempts;
  final ValueChanged<int> onAttemptTap;
  final Future<AttemptDetailModel>? Function(int attemptId) detailFutureFor;

  const _CategorySection({
    required this.categoryName,
    required this.attempts,
    required this.onAttemptTap,
    required this.detailFutureFor,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              categoryName,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(height: 8),
            ...attempts.map((attempt) {
              return _AttemptDetailCard(
                attempt: attempt,
                onTap: () => onAttemptTap(attempt.id),
                detailFuture: detailFutureFor(attempt.id),
              );
            }).toList(),
          ],
        ),
      ),
    );
  }
}

class _AttemptDetailCard extends StatelessWidget {
  final AttemptHistoryModel attempt;
  final VoidCallback onTap;
  final Future<AttemptDetailModel>? detailFuture;

  const _AttemptDetailCard({
    required this.attempt,
    required this.onTap,
    required this.detailFuture,
  });

  @override
  Widget build(BuildContext context) {
    final scoreColor = attempt.scorePercent >= 70
        ? AppColors.accent
        : attempt.scorePercent >= 50
            ? Colors.orange
            : Colors.red;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
                  decoration: BoxDecoration(
                    color: scoreColor.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    '${attempt.scorePercent.toStringAsFixed(0)}%',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: scoreColor,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Completed: ${_formatDate(attempt.submittedAt)}',
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ),
                Text(
                  '${attempt.correctAnswers}/${attempt.totalItems}',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                ),
              ],
            ),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: onTap,
                child: const Text('Open Attempt Review'),
              ),
            ),
            if (detailFuture == null)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Center(child: CircularProgressIndicator()),
              )
            else
              FutureBuilder<AttemptDetailModel>(
                future: detailFuture,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Center(child: CircularProgressIndicator()),
                    );
                  }
                  if (snapshot.hasError) {
                    return const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Text(
                        'Failed to load attempt questions.',
                        style: TextStyle(color: Colors.red),
                      ),
                    );
                  }
                  if (!snapshot.hasData) {
                    return const SizedBox.shrink();
                  }
                  final detail = snapshot.data!;
                  return Column(
                    children: detail.questions.asMap().entries.map((entry) {
                      final index = entry.key + 1;
                      final question = entry.value;
                      return _QuestionHistoryCard(
                        questionNumber: index,
                        question: question,
                      );
                    }).toList(),
                  );
                },
              ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime? date) {
    if (date == null) return 'Unknown';
    try {
      return DateFormat('MMM dd, yyyy HH:mm').format(date);
    } catch (_) {
      return date.toString().substring(0, 10);
    }
  }
}

class _QuestionHistoryCard extends StatelessWidget {
  final int questionNumber;
  final AttemptQuestionDetail question;

  const _QuestionHistoryCard({
    required this.questionNumber,
    required this.question,
  });

  @override
  Widget build(BuildContext context) {
    final isCorrect = question.isCorrect;
    final statusColor =
        isCorrect == true ? AppColors.accent : Colors.red;
    final userAnswer = _formatUserAnswer(question);
    final correctAnswer = _formatCorrectAnswer(question);

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      elevation: 0,
      color: Colors.grey[50],
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Q$questionNumber: ${question.questionText}',
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                  ),
                ),
                if (isCorrect != null)
                  Text(
                    isCorrect ? 'Correct' : 'Incorrect',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: statusColor,
                    ),
                  )
                else
                  const Text(
                    'Review hidden',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.gray600,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text('Your Answer:', style: Theme.of(context).textTheme.labelMedium),
            const SizedBox(height: 4),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isCorrect == true
                    ? AppColors.accent.withOpacity(0.1)
                    : isCorrect == false
                        ? Colors.red.withOpacity(0.1)
                        : Colors.grey[100],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: isCorrect == true
                      ? AppColors.accent
                      : isCorrect == false
                          ? Colors.red
                          : Colors.grey[300]!,
                ),
              ),
              child: Text(
                userAnswer,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: isCorrect == true
                      ? AppColors.accent
                      : isCorrect == false
                          ? Colors.red
                          : null,
                ),
              ),
            ),
            if (correctAnswer != null) ...[
              const SizedBox(height: 8),
              Text('Correct Answer:', style: Theme.of(context).textTheme.labelMedium),
              const SizedBox(height: 4),
              Text(
                correctAnswer,
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _formatUserAnswer(AttemptQuestionDetail question) {
    if (question.questionType == 'short_answer') {
      return question.textAnswer ?? '(No answer)';
    }
    if (question.questionType == 'ordering') {
      final selected = question.options.where((o) => o.isSelected).toList();
      if (selected.isEmpty) return '(No answer)';
      return selected.map((o) => o.text).join(' > ');
    }
    final selected = question.options.where((o) => o.isSelected).toList();
    if (selected.isEmpty) return '(No answer)';
    return selected.map((o) => o.text).join(', ');
  }

  String? _formatCorrectAnswer(AttemptQuestionDetail question) {
    if (question.questionType == 'ordering') {
      final ordered = question.options.where((o) => o.orderIndex != null).toList();
      if (ordered.isEmpty) return null;
      ordered.sort((a, b) => a.orderIndex!.compareTo(b.orderIndex!));
      return ordered.map((o) => o.text).join(' > ');
    }
    final correct = question.options.where((o) => o.isCorrect == true).toList();
    if (correct.isNotEmpty) return correct.map((o) => o.text).join(', ');
    if (question.correctOptionId != null) {
      final match = question.options.firstWhere(
        (o) => o.id == question.correctOptionId,
        orElse: () => const AttemptOption(id: 0, text: '', isSelected: false),
      );
      if (match.text.isNotEmpty) return match.text;
    }
    return null;
  }
}
