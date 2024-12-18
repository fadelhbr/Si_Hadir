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

1. Clone repositori ini:
   ```bash
   git clone https://github.com/username/sihadir.git
   ```
2. Konfigurasi file `auth.php` untuk menyesuaikan dengan pengaturan database, file config berada di `Si_Hadir/web/sihadir/app/auth/auth.php`.
3. Ubah konfigurasi email pada file berikut:
   - `Si_Hadir/web/sihadir/app/handler/email_recovery_handler.php`
   - `Si_Hadir/web/sihadir/app/api/api_send_otp.php`

   Ganti bagian berikut dengan alamat Gmail dan Application Key Anda:
   ```php
   $mail->Username = '*****'; //email username
   $mail->Password = '*****'; // aplication password
   ```

4. Import file SQL ke database MySQL, file database (.sql) berada di `Si_Hadir/database/si_hadir.sql`, penamaan database harus sama dengan nama file, gunakan dbms terbaru (mariadb/mysql server versi 10.11 ke atas dan gunakan >=php 8.1).
5. Jalankan aplikasi di server lokal.
