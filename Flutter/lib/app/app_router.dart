// lib/app/app_router.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../features/auth/providers/auth_provider.dart';
import '../features/auth/presentation/screens/splash_screen.dart';
import '../features/auth/presentation/screens/login_screen.dart';
import '../features/auth/presentation/screens/register_screen.dart';
import '../features/auth/presentation/screens/session_expired_screen.dart';
import '../features/home/student/student_home_screen.dart';
import '../features/categories/presentation/screens/categories_screen.dart';
import '../features/quiz/presentation/screens/quiz_list_screen.dart';
import '../features/quiz/presentation/screens/quiz_screen.dart';
import '../features/quiz/presentation/screens/quiz_result_screen.dart';
import '../features/quiz/presentation/screens/history_screen.dart';
import '../features/quiz/presentation/screens/attempt_detail_screen.dart';

import '../screens/api_test_screen.dart';


GoRouter createRouter(BuildContext context) {
  final auth = Provider.of<AuthProvider>(context, listen: false);

  return GoRouter(
    initialLocation: '/',
    redirect: (ctx, state) {
      final loggedIn     = auth.isLoggedIn;
      final loc          = state.matchedLocation;
      final publicRoutes = ['/', '/login', '/register', '/session-expired', '/api-test'];

      if (!loggedIn && !publicRoutes.contains(loc)) return '/login';

      return null;
    },
    refreshListenable: auth,
    routes: [
      GoRoute(path: '/',                builder: (_, __) => const SplashScreen()),
      GoRoute(path: '/login',           builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/register',        builder: (_, __) => const RegisterScreen()),
      GoRoute(path: '/student-home',    builder: (_, __) => const StudentHomeScreen()),
      GoRoute(path: '/categories',      builder: (_, __) => const CategoriesScreen()),
      GoRoute(
        path: '/categories/:categoryId/quizzes',
        builder: (_, state) {
          final categoryIdParam = state.pathParameters['categoryId'] ?? '0';
          final categoryId = int.tryParse(categoryIdParam) ?? 0;
          final categoryName =
              state.uri.queryParameters['categoryName'] ?? 'Category';
          return QuizListScreen(
            subjectId: categoryId,
            subjectName: categoryName,
          );
        },
      ),
      GoRoute(path: '/quiz',            builder: (_, __) => const QuizScreen()),
      GoRoute(path: '/quiz-result',     builder: (_, __) => const QuizResultScreen()),
      GoRoute(path: '/history',         builder: (_, __) => const HistoryScreen()),
      GoRoute(
        path: '/history/:attemptId',
        builder: (_, state) {
          final idParam = state.pathParameters['attemptId'] ?? '0';
          final attemptId = int.tryParse(idParam) ?? 0;
          return AttemptDetailScreen(attemptId: attemptId);
        },
      ),
      GoRoute(path: '/session-expired', builder: (_, __) => const SessionExpiredScreen()),
      GoRoute(path: '/api-test',        builder: (_, __) => const ApiTestScreen()),

    ],
  );
}
