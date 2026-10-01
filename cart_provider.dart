import 'package:flutter/foundation.dart';
import '../models/beverage.dart';

class CartItem {
  final BeverageModel beverage;
  final String type; // เช่น 'เย็น', 'ร้อน', 'ปั่น'
  final String sweetness; // เช่น '100%', '50%', '0%'
  int quantity;

  CartItem({
    required this.beverage,
    required this.type,
    required this.sweetness,
    this.quantity = 1,
  });

  double get subtotal => beverage.price * quantity;
}

class CartProvider extends ChangeNotifier {
  final List<CartItem> _items = [];

  List<CartItem> get items => _items;

  int get totalCount => _items.fold(0, (sum, item) => sum + item.quantity);

  double get totalPrice => _items.fold(0.0, (sum, item) => sum + item.subtotal);

  void addItem(BeverageModel beverage, String type, String sweetness) {
    // เช็คว่ามีเมนู + ออปชันเดียวกันในตะกร้าหรือยัง
    int index = _items.indexWhere((item) =>
        item.beverage.id == beverage.id &&
        item.type == type &&
        item.sweetness == sweetness);

    if (index >= 0) {
      _items[index].quantity++;
    } else {
      _items.add(CartItem(
        beverage: beverage,
        type: type,
        sweetness: sweetness,
      ));
    }
    notifyListeners();
  }

  void removeItem(int index) {
    _items.removeAt(index);
    notifyListeners();
  }

  void clearCart() {
    _items.clear();
    notifyListeners();
  }
}
