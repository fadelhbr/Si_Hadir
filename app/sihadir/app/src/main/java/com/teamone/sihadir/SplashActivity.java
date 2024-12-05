package com.teamone.sihadir;

import android.animation.AnimatorSet;
import android.animation.ObjectAnimator;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.View;
import android.view.animation.AnticipateOvershootInterpolator;
import android.view.animation.BounceInterpolator;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.preference.PreferenceManager;

import java.io.IOException;
import java.net.InetSocketAddress;
import java.net.Socket;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class SplashActivity extends AppCompatActivity {
    private static final String LOCAL_SERVER_IP = "192.168.0.102";
    private static final int CONNECTION_TIMEOUT = 3000; // 3 seconds timeout

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_splash);

        // Animate splash text (your existing animation code)
        animateSplashText();

        // Check network connectivity
        checkNetworkConnectivity();
    }

    private void animateSplashText() {
        // Find the TextView
        TextView splashText = findViewById(R.id.splash_text);

        // Create complex animator set
        AnimatorSet animatorSet = new AnimatorSet();

        // Fade In Animation
        ObjectAnimator fadeAnimator = ObjectAnimator.ofFloat(splashText, View.ALPHA, 0f, 1f);
        fadeAnimator.setDuration(1000);

        // Scale Animation with Anticipate Overshoot effect
        ObjectAnimator scaleXAnimator = ObjectAnimator.ofFloat(splashText, View.SCALE_X, 0.3f, 1f);
        scaleXAnimator.setDuration(1200);
        scaleXAnimator.setInterpolator(new AnticipateOvershootInterpolator());

        ObjectAnimator scaleYAnimator = ObjectAnimator.ofFloat(splashText, View.SCALE_Y, 0.3f, 1f);
        scaleYAnimator.setDuration(1200);
        scaleYAnimator.setInterpolator(new AnticipateOvershootInterpolator());

        // Rotation Animation
        ObjectAnimator rotationAnimator = ObjectAnimator.ofFloat(splashText, View.ROTATION, -30f, 0f);
        rotationAnimator.setDuration(1000);
        rotationAnimator.setInterpolator(new BounceInterpolator());

        // Translation Animation
        ObjectAnimator translationYAnimator = ObjectAnimator.ofFloat(splashText, View.TRANSLATION_Y, 200f, 0f);
        translationYAnimator.setDuration(1200);
        translationYAnimator.setInterpolator(new AnticipateOvershootInterpolator());

        // Play animations together
        animatorSet.playTogether(fadeAnimator, scaleXAnimator, scaleYAnimator,
                rotationAnimator, translationYAnimator);

        // Start the animation
        animatorSet.start();
    }

    private void checkNetworkConnectivity() {
        // Tambahkan delay setelah animasi selesai
        ExecutorService executor = Executors.newSingleThreadExecutor();
        Handler handler = new Handler(Looper.getMainLooper());

        executor.execute(() -> {
            boolean isConnected = isLocalNetworkAvailable();

            handler.post(() -> {
                if (isConnected) {
                    // Delay 2 detik sebelum melanjutkan ke layar berikutnya
                    new Handler(Looper.getMainLooper()).postDelayed(this::proceedToNextScreen, 2000);
                } else {
                    // Tampilkan layar "No Connection"
                    Intent intent = new Intent(SplashActivity.this, NoConnectionActivity.class);
                    startActivity(intent);
                    overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                    finish();
                }
            });
        });
    }


    private boolean isLocalNetworkAvailable() {
        try {
            Socket socket = new Socket();
            socket.connect(new InetSocketAddress(LOCAL_SERVER_IP, 80), CONNECTION_TIMEOUT);
            socket.close();
            return true;
        } catch (IOException e) {
            return false;
        }
    }

    private void proceedToNextScreen() {
        // Get SharedPreferences
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);

        // Check if it's the first time the app is launched
        boolean isFirstTime = preferences.getBoolean("is_first_time", true);

        Intent intent;
        if (isFirstTime) {
            // If it's the first time, go to OnboardingActivity
            intent = new Intent(SplashActivity.this, OnboardingActivity.class);

            // Mark that onboarding has been shown
            preferences.edit().putBoolean("is_first_time", false).apply();
        } else {
            // If not first time, go to LoginActivity
            intent = new Intent(SplashActivity.this, LoginActivity.class);
        }

        startActivity(intent);
        // Add smooth transition
        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
        finish();
    }
}