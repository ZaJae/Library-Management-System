<?php
session_start();

if (!isset($_GET['method'])) {
    header("Location: return_books.php");
    exit();
}

$paymentMethod = $_GET['method'];
$returnData = isset($_SESSION['return_data']) ? $_SESSION['return_data'] : null;

// If data is in sessionStorage (from JavaScript), we'll need to handle it via POST or store in session
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Details - <?= htmlspecialchars($paymentMethod) ?></title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        html, body {
            height: 100%; 
            margin: 0; 
            padding: 0; 
            background-color: rgba(0,0,0,0.5);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 30px;
            border: 3px solid #dc3545;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-header {
            background-color: #f8d7da;
            padding: 15px;
            margin: -30px -30px 20px -30px;
            border-radius: 7px 7px 0 0;
            border-bottom: 2px solid #dc3545;
            text-align: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #721c24;
            font-size: 24px;
        }

        .penalty-display {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 5px;
            font-size: 20px;
            font-weight: bold;
            color: #721c24;
        }

        .modal-form-group {
            margin-bottom: 20px;
        }

        .modal-form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .modal-form-group input,
        .modal-form-group select {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #aaa;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .modal-button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            align-items: center;
        }

        .modal-button-group button {
            flex: 0 1 auto;
            min-width: 150px;
        }

        .modal-button {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 150px;
        }

        .modal-button.submit {
            background-color: #dc3545 !important;
            color: white !important;
            border: none !important;
        }

        .modal-button.submit:hover {
            background-color: #dc3545 !important;
            color: white !important;
            transform: scale(1.05);
        }

        .modal-button.cancel {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            border: none !important;
        }

        .modal-button.cancel:hover {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Payment Details - <?= htmlspecialchars($paymentMethod) ?></h3>
            </div>
            <div class="penalty-display">
                Payment Method: <?= htmlspecialchars($paymentMethod) ?><br>
                Amount: ₱<span id="paymentAmount">0.00</span>
            </div>
            
            <form id="paymentDetailsForm" method="POST" action="process_payment.php">
                <input type="hidden" name="payment_method" value="<?= htmlspecialchars($paymentMethod) ?>">
                <input type="hidden" name="payment_amount" id="payment_amount" value="">
                <input type="hidden" name="return_data" id="return_data" value="">
            
                <?php if ($paymentMethod === 'GCash' || $paymentMethod === 'PayMaya'): ?>
                    <div class="modal-form-group">
                        <label for="account_name">Account Name:</label>
                        <input type="text" name="account_name" id="account_name" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="account_number">Account Number:</label>
                        <input type="text" name="account_number" id="account_number" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="reference_number">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number" placeholder="Optional">
                    </div>
                <?php elseif ($paymentMethod === 'Bank Transfer'): ?>
                    <div class="modal-form-group">
                        <label for="bank_name">Bank Name:</label>
                        <input type="text" name="bank_name" id="bank_name" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="account_name">Account Name:</label>
                        <input type="text" name="account_name" id="account_name" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="account_number">Account Number:</label>
                        <input type="text" name="account_number" id="account_number" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="reference_number">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number" placeholder="Optional">
                    </div>
                <?php elseif ($paymentMethod === 'Check'): ?>
                    <div class="modal-form-group">
                        <label for="check_number">Check Number:</label>
                        <input type="text" name="check_number" id="check_number" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="bank_name">Bank Name:</label>
                        <input type="text" name="bank_name" id="bank_name" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="account_name">Account Name:</label>
                        <input type="text" name="account_name" id="account_name" required>
                    </div>
                <?php elseif ($paymentMethod === 'Cash'): ?>
                    <div class="modal-form-group">
                        <label for="received_by">Received By:</label>
                        <input type="text" name="received_by" id="received_by" required>
                    </div>
                    <div class="modal-form-group">
                        <label for="receipt_number">Receipt Number:</label>
                        <input type="text" name="receipt_number" id="receipt_number" placeholder="Optional">
                    </div>
                <?php endif; ?>
                
                <div class="modal-button-group">
                    <button type="submit" class="modal-button submit">Confirm Payment</button>
                    <button type="button" class="modal-button cancel" onclick="window.history.back()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    window.addEventListener('DOMContentLoaded', () => {
        // Get return data from sessionStorage
        const returnDataStr = sessionStorage.getItem('returnData');
        if (!returnDataStr) {
            alert('No return data found. Please start from the return books page.');
            window.location.href = 'return_books.php';
            return;
        }
        
        const returnData = JSON.parse(returnDataStr);
        
        // Set payment amount
        document.getElementById('paymentAmount').textContent = parseFloat(returnData.payment_amount).toFixed(2);
        document.getElementById('payment_amount').value = returnData.payment_amount;
        
        // Store return data in hidden field
        document.getElementById('return_data').value = returnDataStr;
        
        // Form validation
        document.getElementById('paymentDetailsForm').addEventListener('submit', function(e) {
            const paymentMethod = '<?= htmlspecialchars($paymentMethod) ?>';
            let isValid = true;
            
            if (paymentMethod === 'GCash' || paymentMethod === 'PayMaya') {
                const accountName = document.getElementById('account_name').value.trim();
                const accountNumber = document.getElementById('account_number').value.trim();
                
                if (!accountName) {
                    alert('Please enter account name');
                    isValid = false;
                } else if (!accountNumber) {
                    alert('Please enter account number');
                    isValid = false;
                }
            } else if (paymentMethod === 'Bank Transfer') {
                const bankName = document.getElementById('bank_name').value.trim();
                const accountName = document.getElementById('account_name').value.trim();
                const accountNumber = document.getElementById('account_number').value.trim();
                
                if (!bankName) {
                    alert('Please enter bank name');
                    isValid = false;
                } else if (!accountName) {
                    alert('Please enter account name');
                    isValid = false;
                } else if (!accountNumber) {
                    alert('Please enter account number');
                    isValid = false;
                }
            } else if (paymentMethod === 'Check') {
                const checkNumber = document.getElementById('check_number').value.trim();
                const bankName = document.getElementById('bank_name').value.trim();
                const accountName = document.getElementById('account_name').value.trim();
                
                if (!checkNumber) {
                    alert('Please enter check number');
                    isValid = false;
                } else if (!bankName) {
                    alert('Please enter bank name');
                    isValid = false;
                } else if (!accountName) {
                    alert('Please enter account name');
                    isValid = false;
                }
            } else if (paymentMethod === 'Cash') {
                const receivedBy = document.getElementById('received_by').value.trim();
                
                if (!receivedBy) {
                    alert('Please enter the name of the person who received the payment');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>

