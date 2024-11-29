package com.teamone.sihadir;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.os.Handler;

import androidx.appcompat.app.AppCompatActivity;
import androidx.preference.PreferenceManager;

public class SplashActivity extends AppCompatActivity {
    private static final int SPLASH_TIMEOUT = 2000; // 2 seconds
    private static final String PREF_IS_FIRST_TIME = "is_first_time";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_splash);

        // Get SharedPreferences
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);

        new Handler().postDelayed(new Runnable() {
            @Override
            public void run() {
                // Check if it's the first time the app is launched
                boolean isFirstTime = preferences.getBoolean(PREF_IS_FIRST_TIME, true);

                Intent intent;
                if (isFirstTime) {
                    // If it's the first time, go to OnboardingActivity
                    intent = new Intent(SplashActivity.this, OnboardingActivity.class);

                    // Mark that onboarding has been shown
                    preferences.edit().putBoolean(PREF_IS_FIRST_TIME, false).apply();
                } else {
                    // If not first time, go to MainActivity or LoginActivity
                    intent = new Intent(SplashActivity.this, LoginActivity.class);
                }

                startActivity(intent);
                finish();
            }
        }, SPLASH_TIMEOUT);
    }
}