package com.teamone.sihadir.activity;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;

import androidx.appcompat.app.AppCompatActivity;

import com.teamone.sihadir.R;

public class NoConnectionActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_no_connection);

        // Inisialisasi tombol retry
        Button btnRetry = findViewById(R.id.btn_retry);

        // Set listener untuk tombol retry
        btnRetry.setOnClickListener(v -> {
            // Coba ulang koneksi dengan kembali ke SplashActivity
            Intent intent = new Intent(NoConnectionActivity.this, SplashActivity.class);
            startActivity(intent);

            // Tambahkan animasi transisi
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);

            // Tutup activity saat ini
            finish();
        });
    }

    // Override metode tombol kembali agar tidak bisa kembali ke activity sebelumnya
    @Override
    public void onBackPressed() {
        // Keluar dari aplikasi
        super.onBackPressed();
        finishAffinity();
    }
}