import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/providers/auth_provider.dart';
import 'login_screen.dart';

class AdminHomeScreen extends StatelessWidget {
  const AdminHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    return Scaffold(
      appBar: AppBar(title: const Text("Admin Home")),
      body: Center(
        child: ElevatedButton(
          onPressed: () async {
            await auth.logout();
            Navigator.pushReplacement(
                context, MaterialPageRoute(builder: (_) => const LoginScreen()));
          },
          child: const Text("Logout"),
        ),
      ),
    );
  }
}