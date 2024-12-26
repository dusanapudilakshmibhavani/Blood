import sys
import mysql.connector
import pywhatkit as kit
from datetime import datetime, timedelta

# Database connection details
db_host = 'localhost'
db_user = 'root'  # Your MySQL username
db_password = 'Bhavani@2005'  # Your MySQL password
db_name = 'donnor_registration1'  # Replace with your database name

# Connect to the database
def connect_to_db():
    try:
        connection = mysql.connector.connect(
            host=db_host,
            user=db_user,
            password=db_password,
            database=db_name
        )
        return connection
    except mysql.connector.Error as err:
        print(f"Error: {err}")
        return None

# Fetch phone numbers for the selected blood group
def fetch_donors_by_blood_group(blood_group):
    connection = connect_to_db()
    if connection:
        cursor = connection.cursor(dictionary=True)
        query = "SELECT name, whatsapp FROM donors WHERE blood_group = %s"
        cursor.execute(query, (blood_group,))
        donors = cursor.fetchall()
        connection.close()

        return donors
    else:
        return []

# Function to send WhatsApp message
def send_whatsapp_message(phone, message, send_time):
    try:
        # Send WhatsApp message using pywhatkit
        kit.sendwhatmsg(phone, message, send_time.hour, send_time.minute, wait_time=20)
    except Exception as e:
        print(f"Failed to send message to {phone}: {e}")

# Function to schedule messages
def send_alerts_for_blood_group(blood_group):
    # Fetch the donors for the selected blood group
    donors = fetch_donors_by_blood_group(blood_group)

    if donors:
        print(f"Sending messages to donors with blood group {blood_group}...\n")

        # Get current time and calculate the send time
        now = datetime.now()
        send_time = now + timedelta(minutes=1, seconds=15)  # Add 1 minute and 15 seconds to the current time

        for donor in donors:
            name = donor['name']
            phone = donor['whatsapp']  # Ensure the phone number includes the country code (e.g., +91)

            # Message to send
            message = f"Dear {name},\nWe urgently need your blood group {blood_group}. Please contact us immediately if available. Thank you for your support!"

            # Send the message to each donor
            send_whatsapp_message(phone, message, send_time)
    else:
        print(f"No donors found for blood group {blood_group}.")

# Main execution
if __name__ == "__main__":
    # Get the blood group from the command line argument passed from PHP
    if len(sys.argv) > 1:
        blood_group_to_search = sys.argv[1]
        send_alerts_for_blood_group(blood_group_to_search)
    else:
        print("No blood group provided!")
