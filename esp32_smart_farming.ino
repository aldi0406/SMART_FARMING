#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <ArduinoJson.h>

// ===== WIFI =====
#define WIFI_SSID "zeldris"
#define WIFI_PASSWORD "achmadmauladi"

// ===== FIREBASE =====
// Base URL WITHOUT trailing .json
const char *FIREBASE_BASE = "https://smart-farming-90ebf-default-rtdb.firebaseio.com";
const char *DEVICE_UID = "ESP32-01";

// ===== OLED =====
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// ===== DHT =====
#define DHTPIN 4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

// ===== PIN =====
#define SOIL_PIN 34
#define RELAY_PIN 23

// ===== KALIBRASI =====
int soilKering = 3200;
int soilBasah = 1500;

// ===== VAR =====
int soilPercent = 0;
float suhu = 0;
float kelembaban = 0;
float lastTemp = NAN;
float lastHum = NAN;
bool pumpStatus = false;  // actual relay state
String pumpMode = "auto"; // "auto" or "manual"
bool serverPumpState = false;

// ===== TIMER =====
unsigned long lastSend = 0;
unsigned long lastDisplay = 0;
unsigned long lastCmdPoll = 0;

// intervals
const unsigned long SEND_INTERVAL = 5000;
const unsigned long DISPLAY_INTERVAL = 1000;
const unsigned long CMD_POLL_INTERVAL = 3000;

void setup()
{
    Serial.begin(115200);

    pinMode(RELAY_PIN, OUTPUT);
    digitalWrite(RELAY_PIN, LOW);

    dht.begin();

    // OLED
    if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C))
    {
        Serial.println("OLED ERROR");
        while (true)
            ;
    }

    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(WHITE);

    display.setCursor(0, 0);
    display.print("INIT...");
    display.display();

    // WIFI
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    Serial.print("Connecting WiFi");
    while (WiFi.status() != WL_CONNECTED)
    {
        delay(300);
        Serial.print(".");
    }

    Serial.println("\nConnected!");
    Serial.println(WiFi.localIP());
}

// read commands from Firebase commands/{DEVICE_UID}.json
void pollCommands()
{
    if (WiFi.status() != WL_CONNECTED)
        return;
    String url = String(FIREBASE_BASE) + "/smart_farming/commands/" + DEVICE_UID + ".json";

    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient https;
    https.begin(client, url);

    int code = https.GET();
    if (code == 200)
    {
        String payload = https.getString();
        if (payload.length() > 2)
        {
            DynamicJsonDocument doc(512);
            DeserializationError err = deserializeJson(doc, payload);
            if (!err)
            {
                if (doc.containsKey("pump_mode"))
                    pumpMode = String((const char *)doc["pump_mode"]);
                if (doc.containsKey("pump_state"))
                    serverPumpState = doc["pump_state"] | false;
            }
        }
    }
    https.end();
}

void sendTelemetry()
{
    if (WiFi.status() != WL_CONNECTED)
        return;

    // build JSON
    DynamicJsonDocument doc(256);
    doc["soil_moisture"] = soilPercent;
    doc["air_temperature"] = !isnan(suhu) ? suhu : lastTemp;
    doc["air_humidity"] = !isnan(kelembaban) ? kelembaban : lastHum;
    doc["pump_status"] = pumpStatus;
    doc["ts"] = millis();

    String json;
    serializeJson(doc, json);

    // POST to /smart_farming/telemetry/{DEVICE_UID}.json so each push is unique
    String url = String(FIREBASE_BASE) + "/smart_farming/telemetry/" + DEVICE_UID + ".json";

    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient https;
    https.begin(client, url);
    https.addHeader("Content-Type", "application/json");

    int code = https.POST(json);
    Serial.print("Telemetry POST code: ");
    Serial.println(code);
    https.end();
}

void applyPumpLogicAuto()
{
    // simple threshold logic
    bool desired = (soilPercent < 40);
    pumpStatus = desired;
}

void applyPumpFromServer()
{
    if (pumpMode == "manual")
    {
        pumpStatus = serverPumpState;
    }
    else
    {
        applyPumpLogicAuto();
    }
}

void loop()
{
    // ===== SOIL =====
    int soilValue = analogRead(SOIL_PIN);
    soilPercent = map(soilValue, soilKering, soilBasah, 0, 100);
    soilPercent = constrain(soilPercent, 0, 100);

    // ===== DHT =====
    float t = dht.readTemperature();
    float h = dht.readHumidity();

    if (!isnan(t) && !isnan(h))
    {
        suhu = t;
        kelembaban = h;
        lastTemp = suhu;
        lastHum = kelembaban;
    }
    else
    {
        Serial.println("DHT read failed, using last-known values");
    }

    // ===== POLL COMMANDS =====
    if (millis() - lastCmdPoll > CMD_POLL_INTERVAL)
    {
        pollCommands();
        lastCmdPoll = millis();
    }

    // ===== APPLY PUMP LOGIC =====
    applyPumpFromServer();
    digitalWrite(RELAY_PIN, pumpStatus ? HIGH : LOW);

    // ===== SEND TELEMETRY =====
    if (millis() - lastSend > SEND_INTERVAL)
    {
        sendTelemetry();
        lastSend = millis();
    }

    // ===== OLED =====
    if (millis() - lastDisplay > DISPLAY_INTERVAL)
    {
        display.clearDisplay();
        display.setCursor(0, 0);
        display.print("Smart Farming");

        display.setCursor(0, 12);
        display.print("Soil: ");
        display.print(soilPercent);

        display.setCursor(0, 24);
        display.print("Temp: ");
        display.print(isnan(suhu) ? lastTemp : suhu);

        display.setCursor(0, 36);
        display.print("Hum: ");
        display.print(isnan(kelembaban) ? lastHum : kelembaban);

        display.setCursor(0, 48);
        display.print(pumpStatus ? "Pump ON" : "Pump OFF");

        display.display();
        lastDisplay = millis();
    }

    // ===== SERIAL =====
    Serial.print("Soil: ");
    Serial.print(soilPercent);
    Serial.print(" | Pump: ");
    Serial.print(pumpStatus ? "ON" : "OFF");
    Serial.print(" | Mode: ");
    Serial.println(pumpMode);

    delay(50);
}
