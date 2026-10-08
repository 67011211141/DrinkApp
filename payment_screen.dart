import 'package:flutter/material.dart';

class PaymentScreen extends StatelessWidget {
  final int orderId;
  final String orderNo;
  final double totalAmount;

  const PaymentScreen({
    super.key,
    required this.orderId,
    required this.orderNo,
    required this.totalAmount,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('ชำระเงิน'),
        backgroundColor: Colors.brown,
        foregroundColor: Colors.white,
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: [
            Text(
              'หมายเลขออเดอร์: $orderNo',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 10),
            Text(
              'ยอดชำระ: ${totalAmount.toStringAsFixed(2)} บาท',
              style: const TextStyle(
                fontSize: 22,
                color: Colors.brown,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 30),
            ElevatedButton(
              onPressed: () {
                Navigator.popUntil(context, (route) => route.isFirst);
              },
              child: const Text('กลับหน้าหลัก'),
            ),
          ],
        ),
      ),
    );
  }
}
