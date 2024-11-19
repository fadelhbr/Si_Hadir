package com.teamone.sihadir;

import android.app.DatePickerDialog;
import android.content.DialogInterface;
import android.os.Build;
import android.os.Bundle;
import android.util.Log;
import android.view.Display;
import android.view.MenuItem;
import android.view.View;
import android.view.WindowManager;
import android.widget.Button;

import androidx.activity.result.ActivityResultLauncher;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;

import com.journeyapps.barcodescanner.ScanContract;
import com.journeyapps.barcodescanner.ScanOptions;
import com.teamone.sihadir.fragment.RiwayatFragment;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.navigation.NavigationBarView;
import com.teamone.sihadir.fragment.BerandaFragment;
import com.teamone.sihadir.fragment.AbsenFragment;
import com.teamone.sihadir.fragment.PengaturanFragment;

<<<<<<< Updated upstream
import java.util.Scanner;
=======

import android.view.View;
import android.widget.Button;
>>>>>>> Stashed changes

public class MainActivity extends AppCompatActivity implements NavigationBarView.OnItemSelectedListener {


    private Button scanButton1;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);


        scanButton1 = findViewById(R.id.scanButton);
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

<<<<<<< Updated upstream
        scanButton1.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Scanner();
=======
        // Menangani klik pada leaveRequestButton
        Button leaveRequestButton = findViewById(R.id.leaveRequestButton);
        leaveRequestButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                loadFragment(new Fragment());
>>>>>>> Stashed changes
            }
        });
    }

<<<<<<< Updated upstream
    private void Scanner() {
        ScanOptions options = new ScanOptions();
        options.setPrompt("Volume up to flash on");
        options.setBeepEnabled(true);
        options.setOrientationLocked(true);
        options.setCaptureActivity(StartScan.class);
        Launcher.launch(options);
    }

    ActivityResultLauncher<ScanOptions> Launcher = registerForActivityResult(new ScanContract(), result -> {
        if (result.getContents() != null ){
            AlertDialog.Builder builder = new AlertDialog.Builder(MainActivity.this);
            builder.setTitle("QR-SCANNER RESULT");
            builder.setMessage(result.getContents());
            builder.setPositiveButton("oke", new DialogInterface.OnClickListener() {
                @Override
                public void onClick(DialogInterface dialog, int which) {
                    dialog.dismiss();
                }
            }).show();
        }
    });

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
=======
    private int findPreferredDisplayMode(float v) {
        return 0;
>>>>>>> Stashed changes
    }

    private boolean loadFragment(Fragment fragment) {
        if (fragment != null) {
            getSupportFragmentManager()
                    .beginTransaction()
                    .replace(R.id.fragment_container, fragment) // Ganti dengan ID container fragment Anda
                    .addToBackStack(null)
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

        return loadFragment(fragment); // Load selected fragment
    }


}
