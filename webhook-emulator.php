<?php
// webhook-emulator.php
// Emulator that uses php to create POST requests to the webhook URL.

// Array of status codes and their corresponding titles
$statusCodes = [
    '9432' => 'Pick up shipment',
    '9870' => 'In transit',
    '9910' => 'Out for delivery',
    '9950' => 'Delivered',
    '9965' => 'Return to sender',
    // Add more status codes as needed
];

// Function to send webhook request
function sendWebhook($url, $apiKey, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'APIKEY: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Add these options to handle CORS and SSL issues
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [$httpCode, "Curl Error: $error"];
    }

    curl_close($ch);
    return [$httpCode, $response];
}

$result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['webhook_url'] ?? '';
    $apiKey = $_POST['api_key'] ?? '';
    $data = [
        'voucher' => $_POST['voucher'] ?? '',
        'statusCode' => $_POST['status_code'] ?? '',
        'statusTitleEN' => $statusCodes[$_POST['status_code']] ?? '',
        'statusTitleGR' => '', // You might want to add Greek titles if available
        'statusDate' => date('Ymd'),
        'statusTime' => date('His'),
        'statusComments' => $_POST['status_comments'] ?? '',
        'statusStation' => $_POST['status_station'] ?? '',
        'statusStationNameEN' => $_POST['station_name'] ?? '',
        'statusStationNameGR' => '', // You might want to add Greek station names if available
        'ReturnVoucher' => $_POST['return_voucher'] ?? ''
    ];

    list($httpCode, $response) = sendWebhook($url, $apiKey, $data);
    $result = "HTTP Code: $httpCode, Response: $response";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Emulator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dark-mode {
            background-color: #333;
            color: #fff;
        }
        .dark-mode .card, .dark-mode .form-control, .dark-mode .btn-primary {
            background-color: #444;
            color: #fff;
        }
        .dark-mode .alert-info {
            background-color: #1c4e7c;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Webhook Emulator</h1>
            <button id="darkModeToggle" class="btn btn-secondary">Toggle Dark Mode</button>
        </div>
        <form method="post" class="mt-4">
            <div class="mb-3">
                <label for="webhook_url" class="form-label">Webhook URL</label>
                <input type="url" class="form-control" id="webhook_url" name="webhook_url" required>
            </div>
            <div class="mb-3">
                <label for="api_key" class="form-label">API Key</label>
                <input type="text" class="form-control" id="api_key" name="api_key" required>
            </div>
            <div class="mb-3">
                <label for="voucher" class="form-label">Voucher</label>
                <input type="text" class="form-control" id="voucher" name="voucher" required>
            </div>
            <div class="mb-3">
                <label for="status_code" class="form-label">Status Code</label>
                <select class="form-control" id="status_code" name="status_code" required>
                    <?php foreach ($statusCodes as $code => $title): ?>
                        <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars("$code - $title") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="status_comments" class="form-label">Status Comments</label>
                <input type="text" class="form-control" id="status_comments" name="status_comments">
            </div>
            <div class="mb-3">
                <label for="status_station" class="form-label">Status Station</label>
                <input type="text" class="form-control" id="status_station" name="status_station">
            </div>
            <div class="mb-3">
                <label for="station_name" class="form-label">Station Name</label>
                <input type="text" class="form-control" id="station_name" name="station_name">
            </div>
            <div class="mb-3">
                <label for="return_voucher" class="form-label">Return Voucher</label>
                <input type="text" class="form-control" id="return_voucher" name="return_voucher">
            </div>
            <button type="submit" class="btn btn-primary">Send Webhook</button>
        </form>
        <?php if ($result): ?>
            <div class="mt-4 alert alert-info">
                <h4>Result:</h4>
                <pre><?= htmlspecialchars($result) ?></pre>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', (event) => {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        // Function to set dark mode
        function setDarkMode(isDark) {
            if (isDark) {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        }

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            setDarkMode(true);
        }

        // Toggle dark mode
        darkModeToggle.addEventListener('click', () => {
            setDarkMode(!body.classList.contains('dark-mode'));
        });

        const statusCodeSelect = document.getElementById('status_code');
        const returnVoucherInput = document.getElementById('return_voucher');

        statusCodeSelect.addEventListener('change', (e) => {
            if (e.target.value === '9965') { // Return to sender
                returnVoucherInput.parentElement.style.display = 'block';
            } else {
                returnVoucherInput.parentElement.style.display = 'none';
            }
        });

        // Trigger the change event on page load
        statusCodeSelect.dispatchEvent(new Event('change'));
    });
    </script>
</body>
</html>
