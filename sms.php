<?php
require_once __DIR__ . '/vendor/autoload.php'; // If you are using Composer dependencies
include 'config.php'; // Include your database configuration

// Infobip credentials
$apiUrl = 'https://jj9pvn.api.infobip.com/whatsapp/1/message/template'; // Replace with your Infobip API endpoint
$apiKey = 'cae015e4871e346989fd84f7bd8817cd-9f428559-8e61-4a2b-a094-26b29c693a4d'; // Replace with your Infobip API Key
$sender = 'whatsapp:+447860099299';
$recipent='whatsapp:+916302437202'; // Replace with your verified Infobip sender number

// Fetch the selected blood group
$blood_group = $_POST['blood_group'] ?? null;

if (!$blood_group) {
    echo "No blood group specified.";
    exit;
}

// Get donors with the selected blood group
$query = "SELECT name, phone FROM donors WHERE blood_group = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $blood_group);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($donor = $result->fetch_assoc()) {
        $name = $donor['name'];
        $phone = "+" . $donor['phone']; // Ensure phone numbers include country code

        // Prepare the message template data
        $data = [
            'messages' => [
                [
                    'from' => $sender,
                    'to' => $phone,
                    'content' => [
                        'templateName' => '1001838927950683', // Replace with actual template name or ID
                        'language' => 'en', // Template language
                        'templateData' => [
                            'body' => [
                                'placeholders' => [
                                    $name, // Dynamic parameter for name
                                    $blood_group // Dynamic parameter for blood group
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Convert the data to JSON
        $dataJson = json_encode($data);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataJson,
            CURLOPT_HTTPHEADER => [
                'Authorization: App ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ]);

 // Initialize an error log file
$errorLogFile = 'error_log.txt';

// ...

// Execute cURL request
$response = curl_exec($ch);
$responseData = json_decode($response, true);

if (curl_errno($ch)) {
    // Log cURL errors
    $error = "Error sending to $phone: " . curl_error($ch) . "\n";
    file_put_contents($errorLogFile, $error, FILE_APPEND);
} else {
    // Check and handle API response
    if (isset($responseData['messages'][0]['status']['name'])) {
        $status = $responseData['messages'][0]['status']['name'];
        $description = $responseData['messages'][0]['status']['description'];
        echo "Response from Infobip for $phone: Status - $status, Description - $description\n";

        if ($status === 'REJECTED_DESTINATION_NOT_REGISTERED') {
            echo "Skipping $phone as it is not registered on WhatsApp.\n";
        } elseif ($status === 'DELIVERED') {
            echo "Message successfully delivered to $phone.\n";
        } else {
            echo "Message to $phone was not delivered. Status: $status.\n";
            // Log delivery errors
            $error = "Error delivering to $phone: $status - $description\n";
            file_put_contents($errorLogFile, $error, FILE_APPEND);
        }
    } else {
        echo "Unexpected response for $phone: $response\n";
        // Log unexpected responses
        $error = "Unexpected response for $phone: $response\n";
        file_put_contents($errorLogFile, $error, FILE_APPEND);
    }
}

// Close cURL session
curl_close($ch);
    }
    echo "Alerts sent successfully!";
} else {
    echo "No donors found for blood group $blood_group.";
}
?>
