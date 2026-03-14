class OptionModel {
  final int id;
  final String optionText;

  OptionModel({
    required this.id,
    required this.optionText,
  });

  factory OptionModel.fromJson(Map<String, dynamic> json) {
    return OptionModel(
      id: json['id'],
      optionText: json['option_text'],
    );
  }
}