import 'package:flutter/material.dart';

class ErrorView extends StatelessWidget {

  final String message;
  final VoidCallback onRetry;

  const ErrorView({
    super.key,
    required this.message,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [

            Text(message),

            const SizedBox(height: 20),

            ElevatedButton(
              onPressed: onRetry,
              child: const Text("Retry"),
            )

          ],
        ),
      ),
    );
  }
}