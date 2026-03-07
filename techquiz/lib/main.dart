// lib/main.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'app/app_theme.dart';
import 'app/app_router.dart';
import 'features/auth/data/auth_repository.dart';
import 'features/auth/providers/auth_provider.dart';
import 'features/categories/data/categories_repository.dart';
import 'features/categories/providers/categories_provider.dart';
import 'features/quiz/data/questions_repository.dart';
import 'features/quiz/providers/quiz_provider.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => AuthProvider(AuthRepository()),
        ),
        ChangeNotifierProvider(
          create: (_) => CategoriesProvider(CategoriesRepository()),
        ),
        // Singleton repo for quiz provider
        Provider<QuestionsRepository>(
          create: (_) => QuestionsRepository(),
        ),
        ChangeNotifierProxyProvider<QuestionsRepository, QuizProvider>(
          create: (ctx) => QuizProvider(ctx.read<QuestionsRepository>()),
          update: (_, repo, prev) => prev ?? QuizProvider(repo),
        ),
      ],
      child: const TechQuizApp(),
    ),
  );
}

class TechQuizApp extends StatelessWidget {
  const TechQuizApp({super.key});

  @override
  Widget build(BuildContext context) {
    final router = createRouter(context);
    return MaterialApp.router(
      title: 'TechQuiz',
      theme: AppTheme.light,
      routerConfig: router,
      debugShowCheckedModeBanner: false,
    );
  }
}
