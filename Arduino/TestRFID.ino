#include <SPI.h>
#include <MFRC522.h>
#include <WiFiS3.h>
#include <ArduinoHttpClient.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

#define RST_PIN 9
#define SS_PIN  10

LiquidCrystal_I2C lcd(0x27, 16, 2);
MFRC522 mfrc522(SS_PIN, RST_PIN);

const int redPin = 7;
const int greenPin = 6;
const int bluePin = 5;

const char* WIFI_SSID = "Mk";
const char* WIFI_PASS = "Satsumaaaa";

const char* SERVER_IP = "10.186.208.207";
const int SERVER_PORT = 80;
const char* SERVER_PATH = "/PAP/api/push.php";

const String API_KEY = "pds_arduino_2026";

WiFiClient wifi;
HttpClient client(wifi, SERVER_IP, SERVER_PORT);

String DEVICE_ID;
String lastUid = "";
unsigned long lastUidSentMs = 0;
const unsigned long UID_COOLDOWN_MS = 10000;

bool connectWiFi(unsigned long timeoutMs = 15000) {
  lcd.clear();
  lcd.print("WiFi...");
  
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - t0) < timeoutMs) {
    delay(300);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    delay(500);
    Serial.println("\nWiFi connected!");
    Serial.print("Arduino IP: ");
    Serial.println(WiFi.localIP());

    lcd.clear();
    lcd.print("WiFi OK");
    delay(1000);
    return true;
  }

  lcd.clear();
  lcd.print("WiFi ERROR");
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
  String s;
  s.reserve(mfrc522.uid.size * 2);

  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) s += "0";
    s += String(mfrc522.uid.uidByte[i], HEX);
  }

  s.toUpperCase();
  return s;
}

void showResultOnLcd(int statusCode, String response) {
  lcd.clear();

  if (statusCode == 200) {
    lcd.setCursor(0, 0);
    lcd.print("Access OK");

    lcd.setCursor(0, 1);
    lcd.print("User found");
  } 
  else if (statusCode == 403) {
    lcd.setCursor(0, 0);
    lcd.print("CARD BLOCKED");
  } 
  else if (statusCode == 404) {
    lcd.setCursor(0, 0);
    lcd.print("CARD NOT FOUND");
  } 
  else if (statusCode < 0) {
    lcd.setCursor(0, 0);
    lcd.print("SERVER ERROR");
  } 
  else {
    lcd.setCursor(0, 0);
    lcd.print("HTTP ERROR:");
    lcd.setCursor(0, 1);
    lcd.print(statusCode);
  }
}

bool postUid(const String& uidHex) {
  if (WiFi.status() != WL_CONNECTED) {
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

  showResultOnLcd(statusCode, response);
  handleLedByStatus(statusCode);

  client.stop();

  return (statusCode >= 200 && statusCode < 300);
}

void setup() {
  Serial.begin(115200);

  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.print("Starting...");

  SPI.begin();
  mfrc522.PCD_Init();

  connectWiFi();
  DEVICE_ID = getMacAsDeviceId();

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("RFID ready");
  lcd.setCursor(0, 1);
  lcd.print("Scan card");

  pinMode(redPin, OUTPUT);
  pinMode(greenPin, OUTPUT);
  pinMode(bluePin, OUTPUT);

  Serial.println("RFID ready. Поднесите карту...");
}

void setColor(int r, int g, int b) {
  analogWrite(redPin, r);
  analogWrite(greenPin, g);
  analogWrite(bluePin, b);
}

void handleLedByStatus(int statusCode) {
  if (statusCode == 200) {
    setColor(0, 255, 0); 
    delay(1000);
    setColor(0, 0, 0);
  }
  else if (statusCode == 403) {
    for (int i = 0; i < 2; i++) {
      setColor(0, 0, 255); 
      delay(300);
      setColor(0, 0, 0);
      delay(300);
    }
  }
  else if (statusCode == 404) {
    setColor(0, 0, 255); 
    delay(1000);
    setColor(0, 0, 0);
  }
  else if (statusCode == 500 || statusCode < 0) {
    setColor(0, 0, 255); 
    delay(1000);
    setColor(0, 0, 0);
  }
}

void loop() {
  if (!mfrc522.PICC_IsNewCardPresent()) return;
  if (!mfrc522.PICC_ReadCardSerial()) return;

  String uidHex = uidToHexString();

  Serial.print("UID: ");
  Serial.println(uidHex);

  unsigned long now = millis();
  if (uidHex == lastUid && (now - lastUidSentMs) < UID_COOLDOWN_MS) {
    Serial.println("Same UID recently sent - pass.");
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    return;
  }

  lcd.clear();
  lcd.print("Sending...");

  if (postUid(uidHex)) {
    lastUid = uidHex;
    lastUidSentMs = now;
  }

  delay(2000);
  lcd.clear();
  lcd.print("RFID ready");
  lcd.setCursor(0, 1);
  lcd.print("Scan card");

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
}