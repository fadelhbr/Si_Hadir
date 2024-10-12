package com.example.hadir;

import android.os.Bundle;
import android.view.MenuItem;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;

import com.example.hadir.fragment.RiwayatFragment;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.navigation.NavigationBarView;
import com.example.hadir.fragment.BerandaFragment;
import com.example.hadir.fragment.AbsenFragment;
import com.example.hadir.fragment.PengumumanFragment;
import com.example.hadir.fragment.PengaturanFragment;

public class DashboardActivity extends AppCompatActivity implements NavigationBarView.OnItemSelectedListener {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dashboard);

        BottomNavigationView navigationView = findViewById(R.id.navigation);
        navigationView.setOnItemSelectedListener(this);

        // Load the default fragment
        if (savedInstanceState == null) {
            loadFragment(new BerandaFragment());
            navigationView.setSelectedItemId(R.id.fr_beranda);
        }
    }

    private boolean loadFragment(Fragment fragment) {
        if (fragment != null) {
            getSupportFragmentManager()
                    .beginTransaction()
                    .replace(R.id.fragment_container, fragment)
                    .commit();
            return true;
        }
        return false;
    }

    @Override
    public boolean onNavigationItemSelected(@NonNull MenuItem item) {
        Fragment fragment = null;
        int itemId = item.getItemId();

        if (itemId == R.id.fr_beranda) {
            fragment = new BerandaFragment();
        } else if (itemId == R.id.fr_riwayatkehadiran) {
            fragment = new RiwayatFragment();
        } else if (itemId == R.id.fr_absen) {
            fragment = new AbsenFragment();
        } else if (itemId == R.id.fr_pengaturan) {
            fragment = new PengaturanFragment();
        }

        return loadFragment(fragment);
    }
}