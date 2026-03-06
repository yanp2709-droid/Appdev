import 'package:flutter/material.dart';

class QuestionsPlaceholderScreen extends StatelessWidget {

  final String categoryName;

  const QuestionsPlaceholderScreen({
    super.key,
    required this.categoryName,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(categoryName),
      ),
      body: const Center(
        child: Text(
          "Questions will load here",
          style: TextStyle(fontSize: 20),
        ),
      ),
    );
  }
}