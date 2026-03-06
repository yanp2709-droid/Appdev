import 'package:flutter/material.dart';

class AdminHomeScreen extends StatelessWidget {
  const AdminHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Admin Home")),
      drawer: Drawer(
        child: ListView(
          children: [
            const DrawerHeader(child: Text("Admin Menu")),
            ListTile(title: const Text("Admin Panel"), onTap: () {}),
            ListTile(title: const Text("Logout"), onTap: () => Navigator.pushReplacementNamed(context, '/login')),
          ],
        ),
      ),
      body: const Center(child: Text("Welcome Admin")),
    );
  }
}