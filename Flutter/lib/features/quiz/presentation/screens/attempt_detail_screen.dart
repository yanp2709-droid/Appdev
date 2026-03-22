// lib/features/quiz/presentation/screens/attempt_detail_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../data/models/attempt_detail.dart';
import '../../../../services/attempt_history_service.dart';

class AttemptDetailScreen extends StatefulWidget {
  final int attemptId;

  const AttemptDetailScreen({super.key, required this.attemptId});

  @override
  State<AttemptDetailScreen> createState() => _AttemptDetailScreenState();
}

class _AttemptDetailScreenState extends State<AttemptDetailScreen> {
  final AttemptHistoryService _historyService = AttemptHistoryService();
  late Future<_AttemptReviewData> _reviewDataFuture;

  @override
  void initState() {
    super.initState();
    _reviewDataFuture = _loadReviewData();
  }

  Future<_AttemptReviewData> _loadReviewData() async {
    final detail = await _historyService.getAttemptDetail(attemptId: widget.attemptId);
    final attemptCount = await _getCategoryAttemptCount(detail.categoryId);
    return _AttemptReviewData(detail: detail, categoryAttemptCount: attemptCount);
  }

  Future<int?> _getCategoryAttemptCount(int categoryId) async {
    const perPage = 50;
    const maxPages = 20;
    var page = 1;
    var totalCount = 0;

    try {
      while (page <= maxPages) {
        final pageItems = await _historyService.getHistory(page: page, perPage: perPage);
        if (pageItems.isEmpty) break;
        totalCount += pageItems.where((a) => a.categoryId == categoryId).length;
        if (pageItems.length < perPage) break;
        page++;
      }
      return totalCount;
    } catch (_) {
      return null;
    }
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
          title: const Text('Attempt Review'),
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
        body: FutureBuilder<_AttemptReviewData>(
          future: _reviewDataFuture,
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
                      'Failed to load attempt details',
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                  ],
                ),
              );
            }

            // Success state
            if (snapshot.hasData) {
              final detail = snapshot.data!.detail;
              final categoryAttemptCount = snapshot.data!.categoryAttemptCount;
              return SingleChildScrollView(
                child: Column(
                  children: [
                    _buildScoreSummary(detail, categoryAttemptCount),
                    const SizedBox(height: 20),
                    ...detail.questions.asMap().entries.map((entry) {
                      final idx = entry.key + 1;
                      final question = entry.value;
                      return _QuestionReview(
                        question: question,
                        questionNumber: idx,
                      );
                    }).toList(),
                  ],
                ),
              );
            }
            return const SizedBox.shrink();
          },
        ),
      ),
    );
  }

  Widget _buildScoreSummary(AttemptDetailModel detail, int? categoryAttemptCount) {
    final scorePercent = detail.scorePercent;
    final scoreColor = scorePercent >= 70
        ? AppColors.accent
        : scorePercent >= 50
            ? Colors.orange
            : Colors.red;

    return Container(
      width: double.infinity,
      color: AppColors.primary,
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          Text(
            detail.categoryName,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Attempts in this category: ${categoryAttemptCount?.toString() ?? 'Unknown'}',
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _SummaryItem(
                label: 'Score',
                value: '${scorePercent.toStringAsFixed(1)}%',
                color: scoreColor,
              ),
              _SummaryItem(
                label: 'Correct',
                value: '${detail.correctAnswers}/${detail.totalItems}',
                color: AppColors.accent,
              ),
              _SummaryItem(
                label: 'Answered',
                value: '${detail.answeredCount}/${detail.totalItems}',
                color: Colors.blue,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SummaryItem extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _SummaryItem({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          label,
          style: const TextStyle(color: Colors.white70, fontSize: 12),
        ),
        const SizedBox(height: 6),
        Text(
          value,
          style: TextStyle(
            color: color,
            fontSize: 20,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    );
  }
}

class _QuestionReview extends StatelessWidget {
  final AttemptQuestionDetail question;
  final int questionNumber;

  const _QuestionReview({
    required this.question,
    required this.questionNumber,
  });

  @override
  Widget build(BuildContext context) {
    final statusColor = question.isCorrect ? AppColors.accent : Colors.red;
    final statusIcon = question.isCorrect ? Icons.check_circle : Icons.cancel;
    final statusText = question.isCorrect ? 'Correct' : 'Incorrect';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Question Header
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Q$questionNumber: ${question.questionType.toUpperCase()}',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: AppColors.primary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        question.questionText,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  children: [
                    Icon(statusIcon, color: statusColor, size: 28),
                    const SizedBox(height: 4),
                    Text(
                      statusText,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: statusColor,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Answer Display
            if (question.questionType == 'short_answer') ...[
              _buildShortAnswerReview(context, question),
            ] else if (question.questionType == 'ordering') ...[
              _buildOrderingReview(context, question),
            ] else ...[
              _buildMultipleChoiceReview(context, question),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildShortAnswerReview(BuildContext context, AttemptQuestionDetail question) {
    final correctAnswer = _getCorrectAnswerText(question);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Your Answer:',
          style: Theme.of(context).textTheme.labelMedium,
        ),
        const SizedBox(height: 8),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey[100],
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey[300]!),
          ),
          child: Text(
            question.textAnswer ?? '(No answer)',
            style: const TextStyle(fontSize: 14),
          ),
        ),
        if (correctAnswer != null) ...[
          const SizedBox(height: 12),
          Text(
            'Correct Answer:',
            style: Theme.of(context).textTheme.labelMedium,
          ),
          const SizedBox(height: 8),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.accent.withOpacity(0.08),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: AppColors.accent),
            ),
            child: Text(
              correctAnswer,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildMultipleChoiceReview(BuildContext context, AttemptQuestionDetail question) {
    var selectedOptions = question.options.where((o) => o.isSelected).toList();
    if (selectedOptions.isEmpty && question.selectedOptionId != null) {
      selectedOptions = question.options.where((o) => o.id == question.selectedOptionId).toList();
    }
    final correctAnswer = _getCorrectAnswerText(question);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        ...question.options.map((option) {
          final isSelected = option.isSelected;
          final isCorrect = option.isCorrect;
          final isWrongSelection = isSelected && !isCorrect;

          Color backgroundColor;
          Color borderColor;
          if (isCorrect) {
            backgroundColor = AppColors.accent.withOpacity(0.1);
            borderColor = AppColors.accent;
          } else if (isWrongSelection) {
            backgroundColor = Colors.red.withOpacity(0.1);
            borderColor = Colors.red;
          } else {
            backgroundColor = Colors.grey[100]!;
            borderColor = Colors.grey[300]!;
          }

          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: backgroundColor,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: borderColor, width: 2),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      option.text,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: isSelected || isCorrect ? FontWeight.w600 : FontWeight.w400,
                      ),
                    ),
                  ),
                  if (isCorrect)
                    Icon(Icons.check_circle, color: AppColors.accent, size: 20)
                  else if (isWrongSelection)
                    Icon(Icons.cancel, color: Colors.red, size: 20),
                ],
              ),
            ),
          );
        }).toList(),
        const SizedBox(height: 8),
        Text('Your Answer:', style: Theme.of(context).textTheme.labelMedium),
        const SizedBox(height: 6),
        Text(
          selectedOptions.isNotEmpty ? selectedOptions.map((o) => o.text).join(', ') : '(No answer)',
          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
        ),
        if (correctAnswer != null) ...[
          const SizedBox(height: 8),
          Text('Correct Answer:', style: Theme.of(context).textTheme.labelMedium),
          const SizedBox(height: 6),
          Text(
            correctAnswer,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
          ),
        ],
      ],
    );
  }

  Widget _buildOrderingReview(BuildContext context, AttemptQuestionDetail question) {
    // For ordering questions, show selected order
    final selectedOptions = question.options.where((o) => o.isSelected).toList();
    final correctOptions = _getCorrectOrderOptions(question);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Your Order:', style: Theme.of(context).textTheme.labelMedium),
        const SizedBox(height: 8),
        if (selectedOptions.isEmpty)
          const Text(
            '(No answer)',
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
          )
        else
          ...selectedOptions.asMap().entries.map((entry) {
            final index = entry.key + 1;
            final option = entry.value;
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.blue),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: Colors.blue,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Center(
                        child: Text(
                          index.toString(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        option.text,
                        style: const TextStyle(fontSize: 14),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        if (correctOptions != null && correctOptions.isNotEmpty) ...[
          const SizedBox(height: 12),
          Text('Correct Order:', style: Theme.of(context).textTheme.labelMedium),
          const SizedBox(height: 8),
          ...correctOptions.asMap().entries.map((entry) {
            final index = entry.key + 1;
            final option = entry.value;
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.accent.withOpacity(0.08),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: AppColors.accent),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: AppColors.accent,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Center(
                        child: Text(
                          index.toString(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        option.text,
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ],
      ],
    );
  }

  String? _getCorrectAnswerText(AttemptQuestionDetail question) {
    final correctOptions = question.options.where((o) => o.isCorrect).toList();
    if (correctOptions.isNotEmpty) {
      return correctOptions.map((o) => o.text).join(', ');
    }
    if (question.correctOptionId != null) {
      final match = question.options.firstWhere(
        (o) => o.id == question.correctOptionId,
        orElse: () => const AttemptOption(id: 0, text: '', isSelected: false, isCorrect: false),
      );
      if (match.text.isNotEmpty) return match.text;
    }
    return null;
  }

  List<AttemptOption>? _getCorrectOrderOptions(AttemptQuestionDetail question) {
    final ordered = question.options.where((o) => o.orderIndex != null).toList();
    if (ordered.isEmpty) return null;
    ordered.sort((a, b) => a.orderIndex!.compareTo(b.orderIndex!));
    return ordered;
  }
}

class _AttemptReviewData {
  final AttemptDetailModel detail;
  final int? categoryAttemptCount;

  const _AttemptReviewData({
    required this.detail,
    required this.categoryAttemptCount,
  });
}
