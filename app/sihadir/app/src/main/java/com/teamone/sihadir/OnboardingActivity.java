package com.teamone.sihadir;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;

import androidx.appcompat.app.AppCompatActivity;
import androidx.viewpager2.widget.ViewPager2;

import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;
import com.teamone.sihadir.adapter.OnboardingAdapter;

import java.util.ArrayList;
import java.util.List;

public class OnboardingActivity extends AppCompatActivity {
    private ViewPager2 viewPager;
    private Button skipButton;
    private Button nextButton;
    private Button finishButton;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_onboarding);

        viewPager = findViewById(R.id.viewPager);
        skipButton = findViewById(R.id.skipButton);
        nextButton = findViewById(R.id.nextButton);
        finishButton = findViewById(R.id.finishButton);
        TabLayout tabLayout = findViewById(R.id.tabLayout);
        List<OnboardingItem> onboardingItems = new ArrayList<>();
        onboardingItems.add(new OnboardingItem(R.drawable.ic_qr_code, "Scan & Presensi",
                "Absensi mudah dan cepat dengan scan QR code menggunakan jaringan lokal yang aman"));
        onboardingItems.add(new OnboardingItem(R.drawable.ic_shield, "Sistem Terpercaya",
                "Dilengkapi sistem keamanan tinggi dengan verifikasi jaringan lokal untuk mencegah kecurangan"));
        onboardingItems.add(new OnboardingItem(R.drawable.ic_clock, "Laporan Real-time",
                "Pantau kehadiran karyawan secara real-time dengan laporan yang akurat dan terperinci"));

        OnboardingAdapter adapter = new OnboardingAdapter(this, onboardingItems);
        viewPager.setAdapter(adapter);

        new TabLayoutMediator(tabLayout, viewPager,
                (tab, position) -> {}).attach();

        skipButton.setOnClickListener(v -> finishOnboarding());
        nextButton.setOnClickListener(v -> {
            if (viewPager.getCurrentItem() < onboardingItems.size() - 1) {
                viewPager.setCurrentItem(viewPager.getCurrentItem() + 1);
            }
        });
        finishButton.setOnClickListener(v -> finishOnboarding());

        viewPager.registerOnPageChangeCallback(new ViewPager2.OnPageChangeCallback() {
            @Override
            public void onPageSelected(int position) {
                updateButtons(position);
            }
        });
    }

    private void updateButtons(int position) {
        if (position == 2) {
            nextButton.setVisibility(View.GONE);
            finishButton.setVisibility(View.VISIBLE);
        } else {
            nextButton.setVisibility(View.VISIBLE);
            finishButton.setVisibility(View.GONE);
        }
        skipButton.setVisibility(position == 2 ? View.GONE : View.VISIBLE);
    }

    private void finishOnboarding() {
        // Navigate to main activity
        startActivity(new Intent(this, LoginActivity.class));
        finish();
    }
}