# ELTA Webhook Toolkit

This project provides a webhook receiver and emulator system designed for tracking ELTA courier shipments. It includes a PHP-based webhook receiver that stores and displays shipment status updates, as well as two emulator versions (PHP and JavaScript) for testing purposes.

## Features

-   Webhook receiver for processing shipment status updates
-   Storage of shipment data in JSON files, organized by voucher number
-   Real-time dashboard displaying recent shipment updates
-   Dark mode toggle with persistent user preference
-   Shipment history viewer
-   PHP-based webhook emulator for server-side testing
-   JavaScript-based webhook emulator for client-side testing
-   CORS support for cross-origin requests
-   Comprehensive error logging system for debugging

## Files

-   `webhook.php`: The main webhook receiver and dashboard
-   `webhook-emulator.php`: PHP-based webhook emulator
-   `webhook-emulator-js.php`: JavaScript-based webhook emulator

## Setup

1. Clone this repository to your web server directory.
2. Ensure PHP is installed and configured on your server.
3. Create a directory named `webhook_data` in the same directory as the PHP files.
4. Set appropriate read/write permissions for the `webhook_data` directory.
5. Update the `API_KEY` constant in `webhook.php` with your chosen API key.

## Usage

### Webhook Receiver (`webhook.php`)

-   Access this file through your web browser to view the shipment status dashboard.
-   The dashboard automatically updates every 5 seconds to show new shipments.
-   Click "View History" on any shipment to see its full status history.

### PHP Emulator (`webhook-emulator.php`)

-   Access this file through your web browser to simulate webhook requests.
-   Fill in the form with shipment details and click "Send Webhook" to test.

### JavaScript Emulator (`webhook-emulator-js.php`)

-   Similar to the PHP emulator but sends requests directly from the browser.
-   Useful for testing in environments where server-side requests might face CORS or SSL issues.

## Error Logging

This project includes a comprehensive error logging system for debugging purposes:

-   Server-side logging: Uses `customErrorLog()` function to log PHP errors and important events.
-   Client-side logging: JavaScript `logToConsole()` function logs client-side events to the browser console.
-   Log rotation: Implements automatic log file rotation to manage log file size.

Developers can use these logging features to troubleshoot issues and monitor the application's behavior.

## API

The webhook receiver expects POST requests with the following JSON structure:

```json
{
    "voucher": "string",
    "statusCode": "string",
    "statusTitleEN": "string",
    "statusTitleGR": "string",
    "statusDate": "string",
    "statusTime": "string",
    "statusComments": "string",
    "statusStation": "string",
    "statusStationNameEN": "string",
    "statusStationNameGR": "string",
    "ReturnVoucher": "string"
}
```

Ensure to include the `APIKEY` header with the correct API key in your requests.

## Contributing

Contributions to this project are welcome. Please fork the repository and submit a pull request with your changes.

## License and Attribution

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

**Important**: Any use, modification, or distribution of this software must include visible attribution to the original author, Androutsos Alexandros, and a link to the original repository: https://github.com/escozul/elta-webhook-toolkit

## Author

Androutsos Alexandros

Initial creation and development by Androutsos Alexandros.
