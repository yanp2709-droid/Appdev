// lib/features/quiz/presentation/screens/history_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../../core/constants/app_colors.dart';
import '../../data/models/attempt_history.dart';
import '../../data/models/attempt_detail.dart';
import '../../../../services/attempt_history_service.dart';
import 'attempt_detail_screen.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final AttemptHistoryService _historyService = AttemptHistoryService();
  late Future<List<AttemptHistoryModel>> _historiesDataFuture;

  @override
  void initState() {
    super.initState();
    _historiesDataFuture = _historyService.getHistory();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Attempt History'),
        centerTitle: true,
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
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
          return ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: attempts.length,
            itemBuilder: (context, index) {
              final attempt = attempts[index];
              return _AttemptCard(
                attempt: attempt,
                onTap: () => _navigateToDetail(context, attempt.id),
              );
            },
          );
        },
      ),
    );
  }

  void _navigateToDetail(BuildContext context, int attemptId) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => AttemptDetailScreen(attemptId: attemptId),
      ),
    );
  }
}

class _AttemptCard extends StatelessWidget {
  final AttemptHistoryModel attempt;
  final VoidCallback onTap;

  const _AttemptCard({
    required this.attempt,
    required this.onTap,
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
      elevation: 2,
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.all(16),
        leading: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: scoreColor.withOpacity(0.2),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            '${attempt.scorePercent.toStringAsFixed(0)}%',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: scoreColor,
            ),
            textAlign: TextAlign.center,
          ),
        ),
        title: Text(
          attempt.categoryName,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w700,
            color: AppColors.primary,
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Questions: ${attempt.answeredCount}/${attempt.totalItems}',
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ),
                Expanded(
                  child: Text(
                    'Correct: ${attempt.correctAnswers}',
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              'Completed: ${_formatDate(attempt.submittedAt)}',
              style: const TextStyle(fontSize: 11, color: Colors.grey),
            ),
          ],
        ),
        trailing: const Icon(Icons.arrow_forward_ios, size: 18, color: Colors.grey),
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
