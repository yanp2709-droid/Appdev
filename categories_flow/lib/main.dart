import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
<<<<<<< HEAD

import 'features/categories/categories_screen.dart';
import 'features/categories/providers/categories_provider.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {

    return MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => CategoriesProvider(),
        ),
      ],
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        title: 'Quiz App',
        home: const CategoriesScreen(),
      ),
    );

=======
import 'core/providers/auth_provider.dart';
import 'features/auth/login_screen.dart';
import 'features/auth/student_home_screen.dart';
import 'features/auth/admin_home_screen.dart';

void main() {
  runApp(
    ChangeNotifierProvider(
      create: (_) => AuthProvider(),
      child: const TechQuizApp(),
    ),
  );
}

class TechQuizApp extends StatelessWidget {
  const TechQuizApp({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context, listen: false);

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'TechQuiz Auth',
      home: FutureBuilder(
        future: auth.loadUser(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(body: Center(child: CircularProgressIndicator()));
          } else {
            if (auth.isAuthenticated) {
              return auth.role == 'student'
                  ? const StudentHomeScreen()
                  : const AdminHomeScreen();
            } else {
              return const LoginScreen();
            }
          }
        },
      ),
    );
>>>>>>> 6989a6f1071698ea32aaec745d984ba93999647b
  }
}