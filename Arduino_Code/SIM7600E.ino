#define SerialMon Serial
HardwareSerial SerialAT(1); // RX: GPIO16, TX: GPIO17

void setup() {
  SerialMon.begin(115200);
  SerialAT.begin(115200, SERIAL_8N1, 16, 17, false);
  delay(1000);

  SerialMon.println("Testing AT commands...");

  // Test modem responses
  SerialAT.println("AT");
  delay(1000);

  SerialAT.println("AT+CSQ");  // Signal quality
  delay(1000);

  SerialAT.println("AT+COPS?");  // Operator info
  delay(1000);

  SerialAT.println("AT+CGREG?");  // Network registration
  delay(1000);
}

void loop() {
  while (SerialAT.available()) {
    char c = SerialAT.read();
    SerialMon.print(c); // Print modem response
  }
}
