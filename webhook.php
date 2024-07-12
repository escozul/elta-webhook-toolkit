<?php
// Webhook receiver for ELTA Courier PostStatus

// Custom error logging function
function rotateLogFile($logFile, $maxSize = 5242880, $keepFiles = 3) { // 5MB default max size
    if (file_exists($logFile) && filesize($logFile) > $maxSize) {
        for ($i = $keepFiles - 1; $i > 0; $i--) {
            if (file_exists($logFile . '.' . $i)) {
                rename($logFile . '.' . $i, $logFile . '.' . ($i + 1));
            }
        }
        rename($logFile, $logFile . '.1');
    }
}

// customErrorLog function with log rotation
function customErrorLog($message) {
    $logFile = __DIR__ . '/webhook_error.log';
    rotateLogFile($logFile);
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Allow cross-origin requests (for development/testing purposes)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, APIKEY");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Your API key for verification
define('API_KEY', 'your-api-key-here');

// Directory to store JSON files
define('STORAGE_DIR', __DIR__ . '/webhook_data');

// Ensure the storage directory exists
if (!file_exists(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0755, true);
}

// Function to verify the API key
function verifyApiKey() {
    $headers = getallheaders();
    $receivedApiKey = '';

    // Case-insensitive search for the API key
    foreach ($headers as $header => $value) {
        if (strtolower($header) === 'apikey') {
            $receivedApiKey = $value;
            break;
        }
    }

    // customErrorLog("Received API Key: " . $receivedApiKey);
    // customErrorLog("Expected API Key: " . API_KEY);

    if ($receivedApiKey !== API_KEY) {
        // customErrorLog("API Key Mismatch!");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API Key']);
        exit;
    }

    // customErrorLog("API Key Verified Successfully");
}

// Function to process the webhook data
function processWebhookData($data) {
    $voucher = $data['voucher'] ?? 'unknown';
    $filename = STORAGE_DIR . '/' . $voucher . '.json';

    if (file_exists($filename)) {
        $existingData = json_decode(file_get_contents($filename), true);
        $existingData['statusHistory'][] = $data;
    } else {
        $existingData = [
            'voucher' => $voucher,
            'statusHistory' => [$data]
        ];
    }

    file_put_contents($filename, json_encode($existingData, JSON_PRETTY_PRINT));
    // customErrorLog("Received webhook for voucher: $voucher with status: " . $data['statusTitleEN']. " and will save to $filename");
    return $filename;
}

// Function to get recent webhook data
function getRecentWebhookData($limit = 10) {
    $files = glob(STORAGE_DIR . '/*.json');
    rsort($files); // Sort files in reverse order (newest first)
    $files = array_slice($files, 0, $limit);
    $data = [];
    foreach ($files as $file) {
        $shipmentData = json_decode(file_get_contents($file), true);
        $latestStatus = end($shipmentData['statusHistory']);
        $latestStatus['voucher'] = $shipmentData['voucher'];
        $data[] = $latestStatus;
    }
    return $data;
}

// Main execution for webhook processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // customErrorLog("Received POST request");
    // customErrorLog("Received headers: " . print_r(getallheaders(), true));
    verifyApiKey();

    // Get JSON data from the request body
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }

    $filename = processWebhookData($data);

    // Respond with success
    http_response_code(200);
    echo json_encode(['status' => 'OK', 'filename' => basename($filename)]);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getRecent') {
    // Better keep the customErrorLog here commented out. It will trigger with every AJAX call!
    // customErrorLog("Received non-POST request: " . $_SERVER['REQUEST_METHOD']);
    // Handle AJAX request for recent data
    echo json_encode(getRecentWebhookData());
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getHistory' && isset($_GET['voucher'])) {
    // Better keep the customErrorLog here commented out. It will trigger with every AJAX call!
    // customErrorLog("Received non-POST request: " . $_SERVER['REQUEST_METHOD']);
    // Handle AJAX request for shipment history
    $filename = STORAGE_DIR . '/' . $_GET['voucher'] . '.json';
    if (file_exists($filename)) {
        echo file_get_contents($filename);
    } else {
        echo json_encode(['error' => 'Voucher not found']);
    }
    exit;
} else {
    // What else? Will this ever trigger?
    customErrorLog("Received non-POST request: " . $_SERVER['REQUEST_METHOD']);
}

// If not a POST or specific GET request, display the HTML interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Status Dashboard</title>
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
            <h1>Shipment Status Dashboard</h1>
            <button id="darkModeToggle" class="btn btn-secondary">Toggle Dark Mode</button>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Latest Status Update</h5>
                <p id="status-display" class="card-text">No updates yet.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Recent Shipments</h5>
                <ul id="shipment-list" class="list-group list-group-flush">
                    <!-- Shipments will be dynamically added here -->
                </ul>
            </div>
        </div>

        <div id="history-modal" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Shipment History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul id="history-list" class="list-group">
                            <!-- History items will be dynamically added here -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function logToConsole(message) {
            console.log(`[${new Date().toISOString()}] ${message}`);
        }
        document.addEventListener('DOMContentLoaded', (event) => {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.body;
            const historyModal = new bootstrap.Modal(document.getElementById('history-modal'));

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

            // Function to display the latest status
            function displayLatestStatus(status) {
                document.getElementById('status-display').innerText = status;
            }

            // Function to add a shipment to the list
            function addShipment(data) {
                const shipmentList = document.getElementById('shipment-list');
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item';
                listItem.innerHTML = `
                    <strong>Voucher:</strong> ${data.voucher}<br>
                    <strong>Status:</strong> ${data.statusTitleEN} (${data.statusCode})<br>
                    <strong>Date:</strong> ${data.statusDate} ${data.statusTime}<br>
                    <strong>Comments:</strong> ${data.statusComments || 'N/A'}<br>
                    <strong>Station:</strong> ${data.statusStationNameEN || 'N/A'}<br>
                    <button class="btn btn-sm btn-info mt-2 view-history" data-voucher="${data.voucher}">View History</button>
                `;
                shipmentList.prepend(listItem);
            }

            // Function to fetch and display shipment history
            function viewShipmentHistory(voucher) {
                fetch(`webhook.php?action=getHistory&voucher=${voucher}`)
                    .then(response => response.json())
                    .then(data => {
                        const historyList = document.getElementById('history-list');
                        historyList.innerHTML = ''; // Clear existing items
                        data.statusHistory.forEach(status => {
                            const listItem = document.createElement('li');
                            listItem.className = 'list-group-item';
                            listItem.innerHTML = `
                                <strong>Status:</strong> ${status.statusTitleEN} (${status.statusCode})<br>
                                <strong>Date:</strong> ${status.statusDate} ${status.statusTime}<br>
                                <strong>Comments:</strong> ${status.statusComments || 'N/A'}<br>
                                <strong>Station:</strong> ${status.statusStationNameEN || 'N/A'}
                            `;
                            historyList.appendChild(listItem);
                        });
                        historyModal.show();
                    })
                    .catch(error => console.error('Error fetching shipment history:', error));
            }

            // Function to fetch and update recent shipments
            function updateRecentShipments() {
                logToConsole('Fetching recent shipments...');
                fetch('webhook.php?action=getRecent')
                    .then(response => response.json())
                    .then(data => {
                        logToConsole(`Received ${data.length} recent shipments`);
                        const shipmentList = document.getElementById('shipment-list');
                        shipmentList.innerHTML = ''; // Clear existing items
                        data.forEach(shipment => {
                            addShipment(shipment);
                            logToConsole(`Added shipment: ${shipment.voucher}`);
                        });
                        if (data.length > 0) {
                            displayLatestStatus(`${data[0].statusTitleEN} (${data[0].statusCode})`);
                            logToConsole(`Updated latest status: ${data[0].statusTitleEN} (${data[0].statusCode})`);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching recent shipments:', error);
                        logToConsole(`Error fetching recent shipments: ${error.message}`);
                    });
            }

            // Event delegation for view history buttons
            document.getElementById('shipment-list').addEventListener('click', (e) => {
                if (e.target.classList.contains('view-history')) {
                    const voucher = e.target.getAttribute('data-voucher');
                    viewShipmentHistory(voucher);
                }
            });

            // Initial update and set interval for periodic updates
            updateRecentShipments();
            setInterval(updateRecentShipments, 5000); // Update every 5 seconds
        });
    </script>
</body>
</html>
