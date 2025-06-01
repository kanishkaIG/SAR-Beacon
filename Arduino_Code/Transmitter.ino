#include <SPI.h>
#include <LoRa.h>
#include <Wire.h>
#include "MAX30100_PulseOximeter.h"
#include <TinyGPSPlus.h>

#define LORA_SS 5
#define LORA_RST 14
#define LORA_DIO0 26
#define REPORTING_PERIOD_MS 1000

// Device ID
const String deviceID = "Device001";

// MAX30100 PulseOximeter
PulseOximeter pox;
uint32_t tsLastReport = 0;
float heartRate = 0;
float spO2 = 0;

// GPS
TinyGPSPlus gps;
HardwareSerial gpsSerial(2); // Use Serial2 for GPS

// LoRa variables
bool loraInitialized = false;
unsigned long lastLoraSendTime = 0;
const unsigned long loraInterval = 15000; // 15 seconds

// Timing variables for non-blocking operation
unsigned long lastGpsUpdateTime = 0;
unsigned long gpsUpdateInterval = 1000; // 1 second

void onBeatDetected() {
    Serial.println("Beat detected!");
}

void setup() {
    Serial.begin(9600);
    Serial.println("Starting system setup...");

    // Initialize LoRa
    initializeLoRa();
    
    // Initialize MAX30100
    initializePulseOximeter();

    // Initialize GPS on Serial2
    gpsSerial.begin(9600, SERIAL_8N1, 16, 17); // RX on 16, TX on 17
    Serial.println("GPS initialized.");
}

void loop() {
    // Call each function independently
    updatePulseOximeter();
    updateGPS();
    sendLoRaData();
}

// Initialize LoRa module
void initializeLoRa() {
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
        loraInitialized = true;
    }
}

// Initialize MAX30100 pulse oximeter
void initializePulseOximeter() {
    Serial.print("Initializing pulse oximeter...");
    if (!pox.begin()) {
        Serial.println("FAILED");
        while (1);
    } else {
        Serial.println("SUCCESS");
    }
    pox.setIRLedCurrent(MAX30100_LED_CURR_7_6MA);
    pox.setOnBeatDetectedCallback(onBeatDetected);
}

// Update pulse oximeter data
void updatePulseOximeter() {
    pox.update();

    // Grab the updated heart rate and SpO2 levels every REPORTING_PERIOD_MS
    if (millis() - tsLastReport > REPORTING_PERIOD_MS) {
        heartRate = pox.getHeartRate();
        spO2 = pox.getSpO2();
        tsLastReport = millis();
    }
    
    // Print valid readings for MAX30100
    if (heartRate > 0 && spO2 > 0) {
        Serial.print("Heart Rate: ");
        Serial.print(heartRate);
        Serial.print(" bpm, SpO2: ");
        Serial.print(spO2);
        Serial.println(" %");
    } else {
        Serial.println("Waiting for valid MAX30100 data...");
    }
}

// Update GPS data
void updateGPS() {
    // Check GPS data every gpsUpdateInterval
    if (millis() - lastGpsUpdateTime > gpsUpdateInterval) {
        lastGpsUpdateTime = millis();

        // Read GPS data
        while (gpsSerial.available() > 0) {
            char c = gpsSerial.read();
            gps.encode(c);
        }

        // Print valid GPS data
        if (gps.location.isValid() && gps.time.isValid() && gps.date.isValid()) {
            Serial.print("GPS Location: Latitude: ");
            Serial.print(gps.location.lat(), 6);
            Serial.print(", Longitude: ");
            Serial.print(gps.location.lng(), 6);
            Serial.print(", Date: ");
            Serial.print(gps.date.day());
            Serial.print("/");
            Serial.print(gps.date.month());
            Serial.print("/");
            Serial.print(gps.date.year());
            Serial.print(", Time: ");
            Serial.print(gps.time.hour());
            Serial.print(":");
            Serial.print(gps.time.minute());
            Serial.print(":");
            Serial.println(gps.time.second());
        } else {
            Serial.println("Waiting for valid GPS data...");
        }
    }
}

// Send data via LoRa
void sendLoRaData() {
    // If enough time has passed and LoRa is initialized, send data
    if (loraInitialized && (millis() - lastLoraSendTime > loraInterval)) {
        lastLoraSendTime = millis();

        // Prepare the LoRa data packet
        String dataPacket = "DeviceID:" + deviceID;

        // Append MAX30100 data if available
        if (heartRate > 0 && spO2 > 0) {
            dataPacket += ",HR:" + String(heartRate, 1) + ",SpO2:" + String(spO2, 1);
        }

        // Append GPS data if available
        if (gps.location.isValid()) {
            dataPacket += ",Lat:" + String(gps.location.lat(), 6) + 
                          ",Lng:" + String(gps.location.lng(), 6);
        }

        // Send data via LoRa
        Serial.println("Sending data packet via LoRa...");
        LoRa.beginPacket();
        LoRa.print(dataPacket);
        LoRa.endPacket();
        Serial.print("Data sent: ");
        Serial.println(dataPacket);  // Verify data format
    }
}
