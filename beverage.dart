class CategoryModel {
  final int id;
  final String name;

  CategoryModel({required this.id, required this.name});

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: int.parse(json['id'].toString()),
      name: json['name'],
    );
  }
}

class BeverageModel {
  final int id;
  final int categoryId;
  final String name;
  final String description;
  final double price;
  final String imageUrl;

  BeverageModel({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.description,
    required this.price,
    required this.imageUrl,
  });

  factory BeverageModel.fromJson(Map<String, dynamic> json) {
    return BeverageModel(
      id: int.parse(json['id'].toString()),
      categoryId: int.parse(json['category_id'].toString()),
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      price: double.parse(json['price'].toString()),
      imageUrl: json['image_url'] ?? '',
    );
  }
}
