import 'package:flutter/material.dart';

class StudentHomeScreen extends StatelessWidget {
  const StudentHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Student Home")),
      drawer: Drawer(
        child: ListView(
          children: [
            const DrawerHeader(child: Text("Student Menu")),
            ListTile(title: const Text("Categories"), onTap: () {}),
            ListTile(title: const Text("Results"), onTap: () {}),
            ListTile(title: const Text("Logout"), onTap: () => Navigator.pushReplacementNamed(context, '/login')),
          ],
        ),
      ),
      body: const Center(child: Text("Welcome Student")),
    );
  }
}