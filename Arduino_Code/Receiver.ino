#include <SPI.h>
#include <LoRa.h>
#include <WiFi.h>
#include <HTTPClient.h>

#define LORA_SS 5
#define LORA_RST 14
#define LORA_DIO0 26

const char* ssid = "Kanishka_Isuru";
const char* password = "NOpassword96";

// Update the server URL to point to the Heroku proxy
String serverUrl = "https://shielded-dusk-28491-b742c0714aa5.herokuapp.com/upload_data";

String receivedData;
String deviceID, heartRate, spo2, latitude, longitude;

void setup() {
  Serial.begin(9600);

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.print(".");
  }
  Serial.println("Connected to WiFi");

  // Initialize LoRa
  Serial.println("Initializing LoRa...");
  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);
  if (!LoRa.begin(433E6)) {
      Serial.println("Starting LoRa failed!");
    } else {
      // Optimize for range
      LoRa.setSpreadingFactor(12);  // Maximize range
      LoRa.setSignalBandwidth(125E3); // Narrow bandwidth
      LoRa.setTxPower(20);          // Max transmit power
      LoRa.setCodingRate4(8);       // Robust error correction
      Serial.println("LoRa initialized with long-range settings.");
    }
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

    // Check if WiFi is connected before sending data
    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;

      http.begin(serverUrl.c_str());
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      http.setTimeout(15000);  // Set a longer timeout for the request

      // Format the data for POST request
      String postData = "deviceID=" + deviceID + "&heart_rate=" + heartRate + "&spo2=" + spo2 + "&latitude=" + latitude + "&longitude=" + longitude;
      Serial.print("Post Data: ");
      Serial.println(postData);

      int httpResponseCode = http.POST(postData);

      // Handle response code
      if (httpResponseCode == 200) {
        String response = http.getString();
        Serial.printf("POST response: %d, %s\n", httpResponseCode, response.c_str());
      } else {
        Serial.printf("Error in POST request: %s\n", http.errorToString(httpResponseCode).c_str());
      }

      http.end();
    } else {
      Serial.println("WiFi disconnected, unable to send data.");
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
