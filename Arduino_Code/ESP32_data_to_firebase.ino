#include <SPI.h>
#include <LoRa.h>
#include <WiFi.h>
#include <FirebaseESP32.h>

// Provide the token generation process info
#include <addons/TokenHelper.h>

// Provide the RTDB payload printing info and other helper functions
#include <addons/RTDBHelper.h>

#define ss 5
#define rst 14
#define dio0 26

// WiFi credentials
const char* ssid = "Kanishka's iPhone";
const char* password = "qwerty12345";

// Firebase credentials
#define API_KEY "AIzaSyCOYtUvfn675mDWpLNyKQKgNZYRcnU2_qI"
#define DATABASE_URL "https://sar-beacon-f5e87-default-rtdb.asia-southeast1.firebasedatabase.app/"

// User authentication credentials
#define USER_EMAIL "kanishkaisuru@gmail.com"
#define USER_PASSWORD "kanishka@SARbeacon96"

// Firebase objects for configuration and authentication
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;

String receivedData;
String deviceID, heartRate, spo2, latitude, longitude;

void setup() {
  Serial.begin(9600);

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to 4G Network...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(300);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("Connected with IP: ");
  Serial.println(WiFi.localIP());

  // Firebase configuration
  config.api_key = API_KEY;
  config.database_url = DATABASE_URL;
  
  // Set up user credentials for authentication
  auth.user.email = USER_EMAIL;
  auth.user.password = USER_PASSWORD;
  
  // Initialize Firebase with the configuration and authentication objects
  Firebase.begin(&config, &auth);
  Firebase.reconnectWiFi(true);

  // Initialize LoRa
  Serial.println("Initializing LoRa Receiver...");
  LoRa.setPins(ss, rst, dio0);
  if (!LoRa.begin(433E6)) {
    Serial.println("Starting LoRa failed!");
    while (1);
  }

  // Optimize for range
  LoRa.setSpreadingFactor(12);  // Maximize range
  LoRa.setSignalBandwidth(125E3); // Narrow bandwidth
  LoRa.setTxPower(20);          // Max transmit power
  LoRa.setCodingRate4(8);       // Robust error correction

  Serial.println("LoRa initialized with long-range settings.");
}

void loop() {
  int packetSize = LoRa.parsePacket();
  if (packetSize) {
    receivedData = "";
    while (LoRa.available()) {
      receivedData += (char)LoRa.read();
    }
    Serial.print("Received: ");
    Serial.println(receivedData);

    // Parse received data into components
    parseData(receivedData);

    // Send data to Firebase
    if (Firebase.ready()) {
      String path = "/devices/" + deviceID;
      
      FirebaseJson json;
      json.set("heart_rate", heartRate);
      json.set("spo2", spo2);
      json.set("latitude", latitude);
      json.set("longitude", longitude);

      if (Firebase.setJSON(fbdo, path.c_str(), json)) {
        Serial.println("Data successfully sent to Firebase");
      } else {
        Serial.println("Failed to send data to Firebase");
        Serial.println(fbdo.errorReason());
      }
    } else {
      Serial.println("Firebase not ready");
    }
  }
}

// Function to parse received LoRa data into device parameters
void parseData(String data) {
  int idIndex = data.indexOf("DeviceID:");
  int hrIndex = data.indexOf("HR:");
  int spIndex = data.indexOf("SpO2:");
  int latIndex = data.indexOf("Lat:");
  int lngIndex = data.indexOf("Lng:");

  if (idIndex != -1 && hrIndex != -1 && spIndex != -1 && latIndex != -1 && lngIndex != -1) {
    deviceID = data.substring(idIndex + 9, data.indexOf(",", idIndex));
    heartRate = data.substring(hrIndex + 3, data.indexOf(",", hrIndex));
    spo2 = data.substring(spIndex + 5, data.indexOf(",", spIndex));
    latitude = data.substring(latIndex + 4, data.indexOf(",", latIndex));
    longitude = data.substring(lngIndex + 4);
  }
}
