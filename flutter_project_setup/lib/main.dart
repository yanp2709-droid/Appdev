import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'app/router.dart';
import 'app/theme.dart';
import 'core/providers/auth_provider.dart';

void main() {
  runApp(const TechQuizApp());
}

class TechQuizApp extends StatelessWidget {
  const TechQuizApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthProvider(), // just the basic provider
      child: MaterialApp(
        title: 'TechQuiz',
        theme: appTheme,
        debugShowCheckedModeBanner: false,
        onGenerateRoute: AppRouter.generateRoute,
        initialRoute: '/login', // start at LoginScreen
      ),
    );
  }
}