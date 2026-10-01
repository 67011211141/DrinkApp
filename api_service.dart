import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/beverage.dart';

class ApiService {
  // ข้อควรระวังเรื่อง IP Address:
  // - Android Emulator ใช้: http://10.0.2.2/mobile_0/api
  // - iOS Simulator / Web ใช้: http://localhost/mobile_0/api
  // - ทดสอบกับมือถือจริง ให้เปลี่ยนเป็น IP เครื่องคอมฯ เช่น http://192.168.1.50/mobile_0/api
  static const String baseUrl = 'http://10.34.9.132/mobile_g3/api';

  static Future<Map<String, dynamic>> fetchBeverages() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/get_beverages.php'));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          List<CategoryModel> categories = (data['categories'] as List)
              .map((c) => CategoryModel.fromJson(c))
              .toList();

          List<BeverageModel> beverages = (data['beverages'] as List)
              .map((b) => BeverageModel.fromJson(b))
              .toList();

          return {
            'categories': categories,
            'beverages': beverages,
          };
        }
      }
      throw Exception('ไม่สามารถดึงข้อมูลจากเซิร์ฟเวอร์ได้');
    } catch (e) {
      throw Exception('เกิดข้อผิดพลาดในการเชื่อมต่อ: $e');
    }
  }
}
