package com.teamone.sihadir;

import android.animation.AnimatorSet;
import android.animation.ObjectAnimator;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.os.Handler;
import android.view.View;
import android.view.animation.AnticipateOvershootInterpolator;
import android.view.animation.BounceInterpolator;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.preference.PreferenceManager;

public class SplashActivity extends AppCompatActivity {
    private static final int SPLASH_TIMEOUT = 2000; // 2 seconds
    private static final String PREF_IS_FIRST_TIME = "is_first_time";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_splash);

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

        // Get SharedPreferences
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);

        // Create a new Handler
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
                // Add smooth transition
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        }, SPLASH_TIMEOUT);
    }
}