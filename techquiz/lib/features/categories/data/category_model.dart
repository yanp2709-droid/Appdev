import 'package:flutter/material.dart';

class CategoryModel {
  final int    id;
  final String name;
  final String subtitle;
  final Color  color;
  final String emoji;
  final int    questions;
  final int    minutes;

  const CategoryModel({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.color,
    required this.emoji,
    required this.questions,
    required this.minutes,
  });

  factory CategoryModel.fromMap(Map<String, dynamic> map) {
    Color parsedColor = const Color(0xFF60A5FA);
    final hex = map['color'] as String?;
    if (hex != null) {
      try {
        parsedColor =
            Color(int.parse(hex.replaceFirst('#', '0xFF')));
      } catch (_) {}
    }
    return CategoryModel(
      id:        (map['id'] as int?) ?? 0,
      name:      (map['name'] as String?) ?? '',
      subtitle:  (map['subtitle'] as String?) ?? 'Categories · Interior categories',
      color:     parsedColor,
      emoji:     (map['emoji'] as String?) ?? '📚',
      questions: (map['questions'] as int?) ?? 10,
      minutes:   (map['minutes'] as int?) ?? 15,
    );
  }
}
