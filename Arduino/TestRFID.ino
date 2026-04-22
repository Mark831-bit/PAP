#include <SPI.h>
#include <MFRC522.h>
#include <WiFiS3.h>
#include <ArduinoHttpClient.h>

#define RST_PIN 9
#define SS_PIN  10

// Wi-Fi
const char* WIFI_SSID = "Mk"; // ☭?
const char* WIFI_PASS = "Satsumaaaa";

// WAMP server IP (ноутбук)
const char* SERVER_IP   = "10.105.70.14";
const int   SERVER_PORT = 80;


const char* SERVER_PATH = "/PAP/api/push.php";

// API
const String API_KEY = "pds_arduino_2026";

// RFID
MFRC522 mfrc522(SS_PIN, RST_PIN);

// HTTP
WiFiClient wifi;
HttpClient client(wifi, SERVER_IP, SERVER_PORT);

String DEVICE_ID;

// антиспам
String lastUid = "";
unsigned long lastUidSentMs = 0;
const unsigned long UID_COOLDOWN_MS = 10000;

// -------- helpers --------
bool connectWiFi(unsigned long timeoutMs = 15000) {
  Serial.print("Connecting WiFi: ");
  Serial.println(WIFI_SSID);

  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - t0) < timeoutMs) {
    delay(300);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected!");
    Serial.print("Arduino IP: ");
    delay(500);
    Serial.println(WiFi.localIP());
    return true;
  }

  Serial.println("\nWiFi connect TIMEOUT");
  return false;
}

String getMacAsDeviceId() {
  byte mac[6];
  WiFi.macAddress(mac);
  char macStr[18];
  sprintf(macStr, "%02X:%02X:%02X:%02X:%02X:%02X",
          mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
  return String(macStr);
}

String uidToHexString() {
  // UID в HEX без пробелов: 08116D8C
  String s;
  s.reserve(mfrc522.uid.size * 2);

  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) s += "0";
    s += String(mfrc522.uid.uidByte[i], HEX);
  }

  s.toUpperCase();
  return s;
}

bool postUid(const String& uidHex) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi lost, reconnect...");
    connectWiFi();
    return false;
  }

  String body =
    "key=" + API_KEY +
    "&device_id=" + DEVICE_ID +
    "&uid=" + uidHex;

  Serial.println("Sending UID to PHP...");
  client.post(SERVER_PATH, "application/x-www-form-urlencoded", body);

  int statusCode = client.responseStatusCode();
  String response = client.responseBody();

  Serial.print("HTTP status: ");
  Serial.println(statusCode);
  Serial.print("Response: ");
  Serial.println(response);

  client.stop(); // важно закрывать соединение

  return (statusCode >= 200 && statusCode < 300);
}

void setup() {
  Serial.begin(115200);
  delay(10000);

  SPI.begin();
  mfrc522.PCD_Init();
  

  connectWiFi();
  DEVICE_ID = getMacAsDeviceId();

  Serial.print("Device ID (MAC): ");
  Serial.println(DEVICE_ID);

  Serial.println("RFID ready. Поднесите карту...");
}

void loop() {
  // ждём карту
  if (!mfrc522.PICC_IsNewCardPresent()) return;
  if (!mfrc522.PICC_ReadCardSerial()) return;

  String uidHex = uidToHexString();

  Serial.print("UID: ");
  Serial.println(uidHex);

  // антиспам на 10 секунд для одинакового UID
  unsigned long now = millis();
  if (uidHex == lastUid && (now - lastUidSentMs) < UID_COOLDOWN_MS) {
    Serial.println("Same UID recently sent - pass.");
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    return;
  }

  // отправка
  if (postUid(uidHex)) {
    lastUid = uidHex;
    lastUidSentMs = now;
  }

  // корректно завершаем чтение
  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
}