#include <ESP8266WiFi.h>
#include <WiFiClientSecure.h>
#include <UniversalTelegramBot.h>
#include <LiquidCrystal_I2C.h>
#include <ESP8266HTTPClient.h> 

#define BOTtoken "8982959133:AAG53DYfKMGliEq01fLjvOziX6okF5tKG1E"
String chat_id = "1365748048";

char ssid[] = "Rzkytyo";
char pass[] = "oajayakannn";

LiquidCrystal_I2C lcd(0x27, 16, 2);
const int trigPin = D5;
const int echoPin = D6;

#define SOUND_VELOCITY 0.034

long duration;
int d_cm;
int H = 76;  
int level;
int s1 = 0, s2 = 0;  
String status_air = "AMAN"; 

WiFiClientSecure client; 
UniversalTelegramBot bot(BOTtoken, client);

int Bot_mtbs = 1000;  
long Bot_lasttime;

unsigned long lastSensorTime = 0;
const long sensorInterval = 2000;  

unsigned long lastDatabaseTime = 0;
const long databaseInterval = 300000; 

void kirim_ke_website(int nilai_level, String status_sekarang) {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient webClient; 
    HTTPClient http;
    
    String url = "http://172.20.10.3/banjir_db/simpan_data.php"; 
    
    http.begin(webClient, url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String dataPost = "level_air=" + String(nilai_level) + "&status=" + status_sekarang;
    
    Serial.println("\n>>> [DATABASE] Mengirim data ke MySQL...");
    int httpResponseCode = http.POST(dataPost);
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.print(">>> [DATABASE] Respon Server: ");
      Serial.println(response); 
    } else {
      Serial.print(">>> [DATABASE] Gagal Kirim. Kode Error: ");
      Serial.println(httpResponseCode);
    }
    http.end();
  }
}

void handleNewMessages(int numNewMessages) {
  for (int i = 0; i < numNewMessages; i++) {
    String current_chat_id = String(bot.messages[i].chat_id);
    String Pesan = bot.messages[i].text;

    if (Pesan == "/cek") {
      baca_level();
      String data_sensor = "Level air = " + String(level) + " cm.\nStatus: " + status_air;
      bot.sendMessage(current_chat_id, data_sensor, "");
    } else if (Pesan == "/start") {
      String welcome = "Sistem IOT Deteksi Banjir Aktif.\nGunakan perintah /cek untuk melihat status air.";
      bot.sendMessage(current_chat_id, welcome, "");
    }
  }
}

void setup() {
  Serial.begin(115200);
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);

  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.print(" Deteksi Banjir ");
  lcd.setCursor(0, 1);
  lcd.print("Telegram NodeMCU");
  delay(3000);

  WiFi.mode(WIFI_STA);
  WiFi.disconnect();
  delay(100);

  lcd.clear();
  lcd.print("Tunggu Koneksi..");
  WiFi.begin(ssid, pass);

  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }

  client.setInsecure();
  lcd.clear();
  lcd.print("Koneksi Sukses !");
  delay(2000);

  lcd.clear();
  lcd.print("Level = ");
  lcd.setCursor(0, 1);
  lcd.print("Status: ");
}

void loop() {
  
  if (millis() > Bot_lasttime + Bot_mtbs) {
    int numNewMessages = bot.getUpdates(bot.last_message_received + 1);
    while (numNewMessages) {
      handleNewMessages(numNewMessages);
      numNewMessages = bot.getUpdates(bot.last_message_received + 1);
    }
    Bot_lasttime = millis();
  }

  if (millis() - lastSensorTime >= sensorInterval) {
    baca_level();
    lastSensorTime = millis();
  }

  if (millis() - lastDatabaseTime >= databaseInterval) {
    Serial.println("\n[JALUR RUTIN] Waktu 5 menit habis.");
    kirim_ke_website(level, status_air);
    lastDatabaseTime = millis(); 
  }
}

void baca_level(){  
  long total_duration = 0;
  int ambil_sampel = 30; 
  
  for (int i = 0; i < ambil_sampel; i++) {
    digitalWrite(trigPin, LOW);
    delayMicroseconds(2); 
    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);
    digitalWrite(trigPin, LOW);  
    total_duration += pulseIn(echoPin, HIGH);
    delay(10); 
  }
  
  duration = total_duration / ambil_sampel; 
  d_cm = duration * SOUND_VELOCITY / 2;  
  
  if (d_cm > H || d_cm <= 0) { d_cm = H; }
  level = (H - d_cm) - 8.0;
  
  Serial.print("[SENSOR] Air: "); Serial.print(level); Serial.print(" cm | Status: "); Serial.println(status_air);

  lcd.setCursor(8, 0); lcd.print("      "); 
  lcd.setCursor(8, 0); lcd.print(level); lcd.print(" cm");       

  if (level > 40) {
    status_air = "BAHAYA"; 
    lcd.setCursor(8, 1); lcd.print("BAHAYA  "); 
    
    if (s2 == 0) { 
      Serial.println("\n [KONDISI DARURAT BAHAYA TERDETEKSI] BYPASS ANTREAN 5 MENIT!");
      
      client.setBufferSizes(512, 512); 
      bot.sendMessage(chat_id, "PERINGATAN: Status BAHAYA! Level air mencapai " + String(level) + " cm!", "");
      
      kirim_ke_website(level, status_air);
      
      s2 = 1; s1 = 0; 
      lastDatabaseTime = millis(); 
    }
  }
  else if (level > 30) {
    status_air = "WASPADA";
    lcd.setCursor(8, 1); lcd.print("WASPADA ");
    
    if (s1 == 0) { 
      Serial.println("\n [KONDISI DARURAT WASPADA TERDETEKSI] BYPASS ANTREAN 5 MENIT!");
      
      client.setBufferSizes(512, 512);
      bot.sendMessage(chat_id, "PERINGATAN: Status WASPADA! Level air mencapai " + String(level) + " cm.", "");
      
      kirim_ke_website(level, status_air);
      
      s1 = 1; s2 = 0; 
      lastDatabaseTime = millis(); 
    }
  }
  else {
    status_air = "AMAN";
    lcd.setCursor(8, 1); lcd.print("AMAN    ");
    
    if (s1 == 1 || s2 == 1) {
      Serial.println("\n-> Status kembali AMAN. Mengunci ulang flag.");
      kirim_ke_website(level, status_air);
      s1 = 0; s2 = 0;
      lastDatabaseTime = millis();
    }
  }
}