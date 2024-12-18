package com.teamone.sihadir.activity;

import android.animation.AnimatorSet;
import android.animation.ObjectAnimator;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.wifi.WifiInfo;
import android.net.wifi.WifiManager;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.view.animation.AnticipateOvershootInterpolator;
import android.view.animation.BounceInterpolator;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.preference.PreferenceManager;

import com.teamone.sihadir.R;

import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class SplashActivity extends AppCompatActivity {
    private static final String ALLOWED_IP = "192.168.0.110";
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
        ConnectivityManager connectivityManager =
                (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);

        if (connectivityManager != null) {
            NetworkInfo networkInfo = connectivityManager.getActiveNetworkInfo();

            if (networkInfo != null && networkInfo.isConnected()) {
                if (networkInfo.getType() == ConnectivityManager.TYPE_WIFI) {
                    WifiManager wifiManager = (WifiManager) getApplicationContext()
                            .getSystemService(Context.WIFI_SERVICE);
                    if (wifiManager != null) {
                        WifiInfo wifiInfo = wifiManager.getConnectionInfo();
                        int ipAddress = wifiInfo.getIpAddress();
                        String deviceIp = String.format("%d.%d.%d.%d",
                                (ipAddress & 0xff),
                                (ipAddress >> 8 & 0xff),
                                (ipAddress >> 16 & 0xff),
                                (ipAddress >> 24 & 0xff));

                        Log.d("SplashActivity", "Device IP: " + deviceIp);
                        Log.d("SplashActivity", "Allowed IP: " + ALLOWED_IP);

                        return ALLOWED_IP.equals(deviceIp);
                    }
                }
            }
        }
        return false;
    }

    private void proceedToNextScreen() {
        boolean isFirstTime = PreferenceManager.getDefaultSharedPreferences(this)
                .getBoolean("is_first_time", true);

        Intent intent = isFirstTime
                ? new Intent(this, OnboardingActivity.class)
                : new Intent(this, LoginActivity.class);

        if (isFirstTime) {
            PreferenceManager.getDefaultSharedPreferences(this)
                    .edit()
                    .putBoolean("is_first_time", false)
                    .apply();
        }

        startActivity(intent);
        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
        finish();
    }
}