package com.teamone.sihadir;

import android.os.Bundle;
import android.util.Log;
import android.view.MenuItem;

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

        BottomNavigationView navigationView = findViewById(R.id.navigation);

        // Menghapus kode untuk "owner" dan langsung menetapkan menu karyawan
        navigationView.getMenu().clear();
        navigationView.inflateMenu(R.menu.menu_karyawan);

        navigationView.setOnItemSelectedListener(this);

        // Load the default fragment
        if (savedInstanceState == null) {
            BerandaFragment berandaFragment = new BerandaFragment();
            Bundle bundle = new Bundle();
            bundle.putString("nama_lengkap", nama_lengkap);
            bundle.putString("role", role);
            berandaFragment.setArguments(bundle); // Set arguments for BerandaFragment
            loadFragment(berandaFragment); // Load the BerandaFragment
            navigationView.setSelectedItemId(R.id.fr_beranda); // Set default selected item
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
        String nama_lengkap = getIntent().getStringExtra("nama_lengkap");
        String role = getIntent().getStringExtra("role");

        if (item.getItemId() == R.id.fr_beranda) {
            Log.d("MainActivity", "Fragment Beranda dipilih.");
            BerandaFragment berandaFragment = new BerandaFragment();
            Bundle bundle = new Bundle();
            bundle.putString("nama_lengkap", nama_lengkap);
            bundle.putString("role", role);
            berandaFragment.setArguments(bundle); // Set arguments for BerandaFragment
            fragment = berandaFragment; // Assign fragment to load
        } else if (item.getItemId() == R.id.fr_riwayatkehadiran) {
            fragment = new RiwayatFragment();
        } else if (item.getItemId() == R.id.fr_absen) {
            fragment = new AbsenFragment();
        } else if (item.getItemId() == R.id.fr_pengaturan) {
            fragment = new PengaturanFragment();
        }
        return loadFragment(fragment); // Load selected fragment
    }
}
