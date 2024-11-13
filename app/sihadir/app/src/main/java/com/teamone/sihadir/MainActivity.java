package com.teamone.sihadir;

import android.os.Build;
import android.os.Bundle;
import android.util.Log;
import android.view.Display;
import android.view.MenuItem;
import android.view.WindowManager;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;

import com.teamone.sihadir.fragment.RiwayatFragment;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.navigation.NavigationBarView;
import com.teamone.sihadir.fragment.BerandaFragment;
import com.teamone.sihadir.fragment.AbsenFragment;
import com.teamone.sihadir.fragment.PengaturanFragment;

public class MainActivity extends AppCompatActivity implements NavigationBarView.OnItemSelectedListener {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // Ambil data nama dari intent
        String nama_lengkap = getIntent().getStringExtra("nama_lengkap");
        String role = getIntent().getStringExtra("role");
        if (nama_lengkap != null) {
            Log.d("MainActivity", "Nama diterima: " + nama_lengkap);
        } else {
            Log.d("MainActivity", "Nama Tidak Ada");
        }

        // Mengatur refresh rate ke 120Hz
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            WindowManager.LayoutParams layoutParams = getWindow().getAttributes();
            layoutParams.preferredDisplayModeId = findPreferredDisplayMode(120f);
            getWindow().setAttributes(layoutParams);
        }
    }

    // Method untuk mencari mode display yang sesuai
    private int findPreferredDisplayMode(float targetRefreshRate) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            Display display = getDisplay();
            if (display != null) {
                Display.Mode[] supportedModes = display.getSupportedModes();
                for (Display.Mode mode : supportedModes) {
                    if (Math.abs(mode.getRefreshRate() - targetRefreshRate) < 0.1f) {
                        return mode.getModeId();
                    }
                }
            }
        }
        return 0; // Return 0 jika tidak menemukan mode yang sesuai
    }

    private boolean loadFragment(Fragment fragment) {

        return false;
    }

    @Override
    public boolean onNavigationItemSelected(@NonNull MenuItem item) {
        Fragment fragment = null;
        String nama_lengkap = getIntent().getStringExtra("nama_lengkap");
        String role = getIntent().getStringExtra("role");


        return loadFragment(fragment); // Load selected fragment
    }
}
