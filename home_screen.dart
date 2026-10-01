import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/beverage.dart';
import '../providers/cart_provider.dart';
import '../services/api_service.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<Map<String, dynamic>> _dataFuture;
  int _selectedCategoryId = 0; // 0 = ทั้งหมด

  @override
  void initState() {
    super.initState();
    _dataFuture = ApiService.fetchBeverages();
  }

  // Dialog สำหรับเลือกประเภทและความหวานก่อนใส่ตะกร้า
  void _showOptionDialog(BeverageModel beverage) {
    String selectedType = 'เย็น';
    String selectedSweetness = '100%';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(beverage.name,
                      style: const TextStyle(
                          fontSize: 20, fontWeight: FontWeight.bold)),
                  Text('${beverage.price.toStringAsFixed(2)} บาท',
                      style:
                          const TextStyle(fontSize: 16, color: Colors.brown)),
                  const Divider(height: 25),
                  const Text('ประเภท:',
                      style: TextStyle(fontWeight: FontWeight.bold)),
                  Row(
                    children: ['ร้อน', 'เย็น', 'ปั่น'].map((type) {
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: ChoiceChip(
                          label: Text(type),
                          selected: selectedType == type,
                          onSelected: (val) =>
                              setModalState(() => selectedType = type),
                        ),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 15),
                  const Text('ระดับความหวาน:',
                      style: TextStyle(fontWeight: FontWeight.bold)),
                  Row(
                    children: ['100%', '50%', '0%'].map((sweet) {
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: ChoiceChip(
                          label: Text(sweet),
                          selected: selectedSweetness == sweet,
                          onSelected: (val) =>
                              setModalState(() => selectedSweetness = sweet),
                        ),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.brown,
                          foregroundColor: Colors.white),
                      onPressed: () {
                        context
                            .read<CartProvider>()
                            .addItem(beverage, selectedType, selectedSweetness);
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                              content:
                                  Text('เพิ่ม ${beverage.name} ลงตะกร้าแล้ว')),
                        );
                      },
                      child: const Text('เพิ่มลงตะกร้า'),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('เมนูเครื่องดื่ม'),
        backgroundColor: Colors.brown,
        foregroundColor: Colors.white,
        actions: [
          // ปุ่มดูตะกร้าสินค้าพร้อม Badge แสดงจำนวน
          Consumer<CartProvider>(
            builder: (context, cart, child) {
              return Badge(
                label: Text(cart.totalCount.toString()),
                isLabelVisible: cart.totalCount > 0,
                child: IconButton(
                  icon: const Icon(Icons.shopping_cart),
                  onPressed: () {
                    // TODO: Navigation ไปหน้า CartScreen
                  },
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.receipt_long),
            onPressed: () {
              // TODO: Navigation ไปหน้า StatusScreen
            },
          ),
        ],
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _dataFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text(snapshot.error.toString()));
          }

          final categories =
              snapshot.data!['categories'] as List<CategoryModel>;
          final beverages = snapshot.data!['beverages'] as List<BeverageModel>;

          final filteredBeverages = _selectedCategoryId == 0
              ? beverages
              : beverages
                  .where((b) => b.categoryId == _selectedCategoryId)
                  .toList();

          return Column(
            children: [
              // Category Filter Bar
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.all(10),
                child: Row(
                  children: [
                    FilterChip(
                      label: const Text('ทั้งหมด'),
                      selected: _selectedCategoryId == 0,
                      onSelected: (val) =>
                          setState(() => _selectedCategoryId = 0),
                    ),
                    const SizedBox(width: 8),
                    ...categories.map((cat) => Padding(
                          padding: const EdgeInsets.only(right: 8),
                          child: FilterChip(
                            label: Text(cat.name),
                            selected: _selectedCategoryId == cat.id,
                            onSelected: (val) =>
                                setState(() => _selectedCategoryId = cat.id),
                          ),
                        )),
                  ],
                ),
              ),

              // Grid แสดงเครื่องดื่ม
              Expanded(
                child: GridView.builder(
                  padding: const EdgeInsets.all(10),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.75,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                  ),
                  itemCount: filteredBeverages.length,
                  itemBuilder: (context, index) {
                    final item = filteredBeverages[index];
                    return Card(
                      elevation: 2,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10)),
                      child: InkWell(
                        onTap: () => _showOptionDialog(item),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: ClipRRect(
                                borderRadius: const BorderRadius.vertical(
                                    top: Radius.circular(10)),
                                child: Image.network(
                                  item.imageUrl,
                                  width: double.infinity,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, __, ___) =>
                                      const Icon(Icons.local_cafe, size: 50),
                                ),
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsets.all(8.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(item.name,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 15)),
                                  Text('${item.price.toStringAsFixed(2)} ฿',
                                      style: const TextStyle(
                                          color: Colors.brown,
                                          fontWeight: FontWeight.bold)),
                                ],
                              ),
                            )
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
