import 'dart:convert';

class UserModel {
  final String name;
  final String email;
  final String role; // 'student' | 'admin'
  final int latestScore;
  final int subjectsCovered;

  const UserModel({
    required this.name,
    required this.email,
    required this.role,
    this.latestScore = 0,
    this.subjectsCovered = 0,
  });

  factory UserModel.fromMap(Map<String, dynamic> map) {
    return UserModel(
      name: (map['name'] as String?) ?? '',
      email: (map['email'] as String?) ?? '',
      role: (map['role'] as String?) ?? 'student',
      latestScore: (map['latest_score'] as int?) ?? 0,
      subjectsCovered: (map['subjects_covered'] as int?) ?? 0,
    );
  }

  Map<String, dynamic> toMap() => {
        'name': name,
        'email': email,
        'role': role,
        'latest_score': latestScore,
        'subjects_covered': subjectsCovered,
      };

  // For SharedPreferences storage
  String toJson() => jsonEncode(toMap());

  factory UserModel.fromJson(String source) =>
      UserModel.fromMap(jsonDecode(source) as Map<String, dynamic>);
}
