# Si Hadir - Sistem Informasi Kehadiran

**Si Hadir** (Sistem Informasi Kehadiran) adalah sistem absensi modern berbasis web dan android mobile app yang dirancang untuk mempermudah pengelolaan kehadiran/presensi sekaligus meningkatkan keamanan dan efisiensi. Si Hadir menyediakan berbagai fitur inovatif sekaligus memastikan bahwa proses absensi berjalan lancar, aman, dan terintegrasi dengan kebijakan internal.

---

## Fitur Utama

- **📍 Absensi Hanya di Lokasi Kantor**

  Memanfaatkan validasi lokasi, absensi hanya dapat dilakukan di area kantor  melalui validasi IP Address atau jaringan lokal.

  

- **🔒 Keamanan Akun Terjamin**

  Sistem memastikan bahwa setiap akun hanya dapat digunakan oleh pemiliknya. Validasi perangkat dan aktivitas pengguna mencegah karyawan saling bertukar akun.

  

- **📊 Laporan Kehadiran Otomatis**

  Sistem menghasilkan laporan yang terperinci untuk evaluasi dan pengambilan keputusan.

  

- **📱 Absensi dengan QR Code dan Kode Unik**

  Mempercepat proses absensi menggunakan teknologi QR Code dan kode unik, sehingga lebih praktis dan efisien.

  

- **📑 Pengajuan Cuti dan Izin Online**

  Pengajuan cuti atau izin dilakukan secara online dengan alur persetujuan yang terintegrasi.

  

- **👀 Monitoring Kehadiran Real-Time**

  Pantau kehadiran secara langsung dengan pembaruan data kehadiran secara real-time.

  

- **✉️ OTP Email Recovery**

  Pemulihan akun yang aman melalui pengiriman kode OTP ke email pengguna.

  

- **⚡ Sistem Cepat dan Aman**

  Sistem dirancang untuk memberikan pengalaman pengguna yang cepat tanpa mengorbankan keamanan data.

---

## Teknologi yang Digunakan

Si Hadir dibangun dengan teknologi berikut:
- **Backend**: PHP
- **Frontend**: JavaScript, HTML, CSS
- **Database**: MySQL

---
## Instalasi

### 1. Clone Repositori
Clone repositori ini ke dalam server atau komputer lokal dengan perintah berikut:
```bash
git clone https://github.com/username/sihadir.git
```

### 2. Konfigurasi File `auth.php`
File konfigurasi untuk autentikasi ada di `Si_Hadir/web/sihadir/app/auth/auth.php`. Sesuaikan pengaturan database di dalam file ini, seperti username, password, dan host database Anda.

### 3. Konfigurasi Email
Ubah pengaturan email pada dua file berikut:
- `Si_Hadir/web/sihadir/app/handler/email_recovery_handler.php`
- `Si_Hadir/web/sihadir/app/api/api_send_otp.php`

Ganti bagian berikut dengan informasi akun Gmail dan Application Key:
```php
$mail->Username = '*****'; // email username
$mail->Password = '*****'; // application password
```

**Catatan:** Pastikan menggunakan **App Password** dari akun Gmail. Gmail akan menolak login langsung menggunakan password biasa untuk aplikasi pihak ketiga.

### 4. Import File SQL ke Database MySQL
File database `.sql` tersedia di `Si_Hadir/database/si_hadir.sql`. Import file ini ke dalam database MySQL. Nama database harus sesuai dengan nama file, dan pastikan menggunakan DBMS terbaru (MariaDB/MySQL versi 10.11 ke atas) serta PHP >= 8.1.

Untuk mengimpor file SQL, jalankan perintah berikut:
```bash
mysql -u username -p database_name < Si_Hadir/database/si_hadir.sql
```

### 5. Jalankan Aplikasi di Server Lokal
Jika menjalankan aplikasi di server lokal, pastikan untuk mengaktifkan **Event Scheduler** di MariaDB/MySQL untuk menjalankan tugas terjadwal. Event scheduler dapat diaktifkan dengan perintah berikut:
```sql
SET GLOBAL event_scheduler = ON;
```

Jika menggunakan hosting atau remote server yang tidak dizinkan akses root, maka perlu menggunakan **cron job** untuk menjalankan event secara terjadwal. Tambahkan entri berikut pada file crontab untuk menjalankan perintah setiap 1 menit atau lebih cepat:
```bash
* * * * * /usr/bin/php /path/to/your/sihadir/web/sihadir/command.php
```

**Catatan:** Untuk server lokal, pastikan event scheduler diaktifkan agar aplikasi dapat memproses tugas terjadwal. Jika menggunakan hosting, gunakan cron job untuk menangani proses secara otomatis.

---
