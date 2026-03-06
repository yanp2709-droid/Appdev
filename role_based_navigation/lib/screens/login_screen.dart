import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../models/user_model.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController emailController = TextEditingController();
  final TextEditingController roleController = TextEditingController(); // type 'student' or 'admin'

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);

    return Scaffold(
      appBar: AppBar(title: const Text("Login")),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: emailController, decoration: const InputDecoration(labelText: 'Email')),
            TextField(controller: roleController, decoration: const InputDecoration(labelText: 'Role (student/admin)')),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: () async {
                final user = UserModel(email: emailController.text, role: roleController.text);
                await auth.saveUser(user);

                if (user.role == 'student') {
                  Navigator.pushReplacementNamed(context, '/student');
                } else {
                  Navigator.pushReplacementNamed(context, '/admin');
                }
              },
              child: const Text("Login"),
            ),
          ],
        ),
      ),
    );
  }
}