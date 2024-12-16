<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <style>
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .payment-container {
            background: white;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .payment-container h2 {
            color: #003366;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        .payment-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .payment-container input, .payment-container select {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .payment-container button {
            background-color: #003366;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            width: 100%;
        }
        .payment-container button:hover {
            background-color: #002244;
        }

    </style>
</head>
<body>
    <?php include 'Head_and_Foot\header.php'; ?>
    <main>
        <div class="payment-container">
            <h2>Payment Page</h2>
            <form action="#" method="POST">
                <label for="payment-method">Select Payment Method</label>
                <select id="payment-method" name="payment-method" required>
                    <option value="">-- Choose Payment Method --</option>
                    <option value="credit-card">Credit/Debit Card</option>
                    <option value="online-banking">Online Banking</option>
                </select>

                <div id="banking-options" style="display: none;">
                    <label for="bank">Select Your Bank</label>
                    <select id="bank" name="bank">
                        <option value="">-- Choose Your Bank --</option>
                        <option value="maybank">Maybank</option>
                        <option value="cimb">CIMB</option>
                        <option value="public-bank">Public Bank</option>
                        <option value="rhb">RHB Bank</option>
                        <option value="hong-leong">Hong Leong Bank</option>
                        <option value="ambank">AmBank</option>
                        <option value="bank-islam">Bank Islam</option>
                    </select>
                </div>

                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" placeholder="Enter amount" required>

                <button type="submit">Pay Now</button>
            </form>
        </div>
    </main>

    <script>
        const paymentMethod = document.getElementById('payment-method');
        const bankingOptions = document.getElementById('banking-options');

        paymentMethod.addEventListener('change', function() {
            if (this.value === 'online-banking') {
                bankingOptions.style.display = 'block';
            } else {
                bankingOptions.style.display = 'none';
            }
        });
    </script>

    <?php include 'Head_and_Foot\footer.php'; ?>
</body>
</html>
