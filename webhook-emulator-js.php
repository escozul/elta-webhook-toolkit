<?php
// webhook-emulator-js.php
// Javascript based implementation to avoid CORS and SSL issues.

// Array of status codes and their corresponding titles
$statusCodes = [
    '9432' => 'Pick up shipment',
    '9870' => 'In transit',
    '9910' => 'Out for delivery',
    '9950' => 'Delivered',
    '9965' => 'Return to sender',
    // Add more status codes as needed
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Emulator (JavaScript Version)</title>
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
        .dark-mode .btn-secondary {
            background-color: #666;
            border-color: #666;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Webhook Emulator (JavaScript Version)</h1>
            <button id="darkModeToggle" class="btn btn-secondary">Toggle Dark Mode</button>
        </div>
        <p class="mb-4">This version makes the request from the user's computer instead of the server.</p>
        <form id="webhookForm" class="mt-4">
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
        <div id="result" class="mt-4 alert alert-info" style="display: none;">
            <h4>Result:</h4>
            <pre id="resultContent"></pre>
        </div>
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

        // Handle form submission
        const form = document.getElementById('webhookForm');
        const resultDiv = document.getElementById('result');
        const resultContent = document.getElementById('resultContent');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const url = formData.get('webhook_url');
            const apiKey = formData.get('api_key');

            const data = {
                voucher: formData.get('voucher'),
                statusCode: formData.get('status_code'),
                statusTitleEN: statusCodeSelect.options[statusCodeSelect.selectedIndex].text.split(' - ')[1],
                statusTitleGR: '',
                statusDate: new Date().toISOString().slice(0, 10).replace(/-/g, ''),
                statusTime: new Date().toTimeString().slice(0, 8).replace(/:/g, ''),
                statusComments: formData.get('status_comments'),
                statusStation: formData.get('status_station'),
                statusStationNameEN: formData.get('station_name'),
                statusStationNameGR: '',
                ReturnVoucher: formData.get('return_voucher')
            };

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'APIKEY': apiKey
                    },
                    body: JSON.stringify(data)
                });

                const responseText = await response.text();
                resultContent.textContent = `HTTP Code: ${response.status}, Response: ${responseText}`;
                resultDiv.style.display = 'block';
            } catch (error) {
                resultContent.textContent = `Error: ${error.message}`;
                resultDiv.style.display = 'block';
            }
        });
    });
    </script>
</body>
</html>
