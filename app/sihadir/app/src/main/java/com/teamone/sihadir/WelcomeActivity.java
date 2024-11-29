package com.teamone.sihadir;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.view.animation.Animation;
import android.view.animation.AnimationUtils;
import android.widget.Button;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.preference.PreferenceManager;

public class WelcomeActivity extends AppCompatActivity {

    private Button btnStart;
    private TextView txtWelcome;

    // SharedPreferences keys (pastikan sama dengan yang di LoginActivity)
    private static final String PREF_IS_LOGGED_IN = "is_logged_in";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Periksa apakah sudah login
        if (isLoggedIn()) {
            // Jika sudah login, langsung ke MainActivity
            Intent intent = new Intent(WelcomeActivity.this, MainActivity.class);
            startActivity(intent);
            finish(); // Tutup WelcomeActivity
            return;
        }

        // Jika belum login, tampilkan layar welcome
        setContentView(R.layout.activity_welcome);

        btnStart = findViewById(R.id.btnStart);
        txtWelcome = findViewById(R.id.txtWelcome);

        // Animasi fade in
        Animation fadeIn = AnimationUtils.loadAnimation(this, R.anim.fade_in);
        txtWelcome.startAnimation(fadeIn);

        btnStart.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Animasi button saat diklik
                v.startAnimation(AnimationUtils.loadAnimation(WelcomeActivity.this, R.anim.button_scale));

                // Pindah ke Activity Login
                Intent intent = new Intent(WelcomeActivity.this, LoginActivity.class);
                startActivity(intent);
            }
        });
    }

    private boolean isLoggedIn() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);
        return preferences.getBoolean(PREF_IS_LOGGED_IN, false);
    }
}