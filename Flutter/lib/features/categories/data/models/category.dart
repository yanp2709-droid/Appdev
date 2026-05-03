import 'package:flutter/material.dart';

class CategoryModel {
  final int id;
  final String name;
  final String description;
  final String? academicYear;


  const CategoryModel({
    required this.id,
    required this.name,
    required this.description,
    this.academicYear,
  });


  /// Subtitle generated from description
  String get subtitle => 'Categories · $description';

  /// Emoji generated based on category name
  String get emoji {
    final nameL = name.toLowerCase();
    if (nameL.contains('math') || nameL.contains('number')) return '🔢';
    if (nameL.contains('science') || nameL.contains('physics') || nameL.contains('chemistry')) return '🔬';
    if (nameL.contains('history')) return '📜';
    if (nameL.contains('tech') || nameL.contains('computer') || nameL.contains('programming')) return '💻';
    if (nameL.contains('language') || nameL.contains('english')) return '📖';
    if (nameL.contains('art') || nameL.contains('design')) return '🎨';
    if (nameL.contains('music')) return '🎵';
    if (nameL.contains('sport') || nameL.contains('fitness')) return '⚽';
    return '📚'; // Default
  }

  /// Color generated based on category name
  Color get color {
    final nameL = name.toLowerCase();
    if (nameL.contains('math') || nameL.contains('number')) return const Color(0xFF60A5FA);
    if (nameL.contains('science')) return const Color(0xFFF87171);
    if (nameL.contains('history')) return const Color(0xFFFBBF24);
    if (nameL.contains('tech') || nameL.contains('computer')) return const Color(0xFF34D399);
    if (nameL.contains('language') || nameL.contains('english')) return const Color(0xFFA78BFA);
    if (nameL.contains('art') || nameL.contains('design')) return const Color(0xFFFB7185);
    return const Color(0xFF60A5FA); // Default blue
  }

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: json['id'] as int? ?? 0,
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      academicYear: json['academic_year'] as String?,
    );
  }


  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'description': description,
    if (academicYear != null) 'academic_year': academicYear,
  };


  @override
  String toString() => 'CategoryModel(id: $id, name: $name)';
}
