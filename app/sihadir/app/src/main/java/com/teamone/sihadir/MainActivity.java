package com.teamone.sihadir;

import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentTransaction;

import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.teamone.sihadir.fragment.AbsenFragment;
import com.teamone.sihadir.fragment.HistoryFragment;
import com.teamone.sihadir.fragment.LogoutFragment;
import com.teamone.sihadir.fragment.PermissionFragment;

public class MainActivity extends AppCompatActivity {

    private BottomNavigationView bottomNavigationView;
    private Fragment selectedFragment = null;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_botnav);

        bottomNavigationView = findViewById(R.id.bottom_navigation);

        // Set default fragment
        selectedFragment = new AbsenFragment();
        getSupportFragmentManager().beginTransaction().replace(R.id.fragment_container, selectedFragment).commit(); // Ganti dengan ID fragment_container

        // Set listener for Bottom Navigation using if-else
        bottomNavigationView.setOnNavigationItemSelectedListener(item -> {
            if (item.getItemId() == R.id.fr_riwayat) {
                selectedFragment = new HistoryFragment();
            } else if (item.getItemId() == R.id.fr_perizinan) {
                selectedFragment = new PermissionFragment();
            } else if (item.getItemId() == R.id.fr_logout) {
                selectedFragment = new LogoutFragment();
            } else if (item.getItemId() == R.id.fr_absen) {
            selectedFragment = new AbsenFragment();
            }

            FragmentTransaction transaction = getSupportFragmentManager().beginTransaction();
            transaction.replace(R.id.fragment_container, selectedFragment);
            transaction.commit();

            return true;
        });
    }
}
