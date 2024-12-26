<?php
require_once __DIR__ . '/vendor/autoload.php'; // Correct the path as needed
include 'config.php';

use Twilio\Rest\Client;

// Twilio credentials
$account_sid = 'ACe468e0826d73702b057f0b5341a48e68';
$auth_token =  '0ab6c11c95e336adababc7ee38e5a1e7';
$twilio_number = 'whatsapp:+14155238886';

// Fetch the selected blood group
$blood_group = $_POST['blood_group'];

// Get donors with the selected blood group
$query = "SELECT name, phone FROM donors WHERE blood_group = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $blood_group);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $client = new Client($account_sid, $auth_token);

    while ($donor = $result->fetch_assoc()) {
        $name = $donor['name'];
        $phone = 'whatsapp:+' . $donor['phone']; // Ensure phone numbers include country code

        $message = "Dear $name,\nWe urgently need your blood group $blood_group. If available, please contact us immediately.\nThank you for your support!";

        try {
            $client->messages->create($phone, [
                'from' => $twilio_number,
                'body' => $message,
            ]);
        } catch (Exception $e) {
            echo "Error sending to $phone: " . $e->getMessage();
        }
    }

    echo "Alerts sent successfully!";
} else {
    echo "No donors found for blood group $blood_group.";
}
?>