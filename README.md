# Real-Time-IoT-Flood-Monitoring-Dashboard-System
Sistem pemantauan tingkat air dan mitigasi banjir berbasis Internet of Things yang dirancang untuk memberikan visibilitas data secara real-time kepada pihak berwenang atau instansi terkait. Proyek ini dikembangkan sebagai solusi untuk mendeteksi potensi bencana secara dini melalui otomatisasi pengumpulan data sensor dan antarmuka web responsif.

## 🛠️ Arsitektur Sistem & Alur Kerja
1. **IoT Layer (Hardware):** Mikrokontroler ESP8266 membaca data tingkat air dari sensor secara berkala.
2. **Communication Layer:** Data dikirimkan secara nirkabel dari perangkat keras ke server.
3. **Backend & Database:** PHP memproses data masuk dan menyimpannya ke database untuk keperluan pencatatan dan riwayat.
4. **Frontend Layer (Dashboard):** Antarmuka web responsif berbasis PHP, JavaScript, dan CSS untuk memantau status kondisi air secara langsung, manajemen warga, dan pelaporan.

## 🚀 Tech Stack
* **Microcontroller:** ESP8266 (`/firmware`)
* **Backend:** PHP, MySQL (`/`)
* **Frontend:** HTML, CSS (`style.css`), JavaScript (`script.js`)

## 📂 Struktur Repositori
```text
├── firmware/         # Kode program untuk mikrokontroler ESP8266 (.ino)
├── assets/           # Berisi file pendukung (CSS & JavaScript)
│   ├── css/
│   └── js/
├── login.php           # Dasbor khusus admin/petugas
├── logout.php          # Dasbor khusus warga
├── admin_dashboard.php # Dasbor khusus admin/petugas
├── warga_dashboard.php # Dasbor khusus warga
├── kelola_warga.php    # Dasbor khusus admin/petugas
├── data_warga.php      # Dasbor khusus admin/petugas
├── laporan.php         # Dasbor khusus admin/petugas
├── riwayat.php         # Dasbor ketinggian air admin dan warga
├── riwayat_warga.php   # Dasbor khusus admin/petugas 
├── koneksi.php         # Konfigurasi koneksi database
├── simpan_data.php     # Endpoint API untuk menerima data dari ESP8266
└── README.md
