package com.teamone.sihadir.fragment;

import android.content.Context;
import android.content.SharedPreferences;
import android.os.Build;
import android.os.Bundle;
import android.util.Log;
import android.view.Display;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.view.WindowManager;
import android.widget.Button;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.fragment.app.Fragment;
import androidx.preference.PreferenceManager;

import com.journeyapps.barcodescanner.ScanContract;
import com.journeyapps.barcodescanner.ScanOptions;
import com.teamone.sihadir.R;
import com.teamone.sihadir.StartScan;

import com.google.android.material.textfield.TextInputEditText;

public class AbsenFragment extends Fragment {

    private Button scanButton1;
    private TextView TVnamaLengkap;
    private TextView TVemployeeId;
    private TextInputEditText codeInput;

    private final ActivityResultLauncher<ScanOptions> launcher = registerForActivityResult(new ScanContract(), result -> {
        if (result.getContents() != null) {
            codeInput.setText(result.getContents());
            // Optional dialog konfirmasi
            AlertDialog.Builder builder = new AlertDialog.Builder(requireContext());
            builder.setTitle("Scan Berhasil");
            builder.setMessage("Kode absensi telah terisi");
            builder.setPositiveButton("OK", (dialog, which) -> dialog.dismiss());
            builder.show();
        }

    });

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        // Menginflate layout fragment dan mengembalikannya
        View view = inflater.inflate(R.layout.fragment_absen, container, false);

        // Inisialisasi TextView
        TVnamaLengkap = view.findViewById(R.id.TVnamaLengkap);
        TVemployeeId = view.findViewById(R.id.TVemployeeId);

        // Ambil data dari SharedPreferences
        SharedPreferences sharedPreferences = PreferenceManager.getDefaultSharedPreferences(requireContext());
        String namaLengkap = sharedPreferences.getString("nama_lengkap", "Nama tidak tersedia");
        int userId = sharedPreferences.getInt("user_id", 0); // Default 0 jika tidak ditemukan

        // Tampilkan data di TextView
        TVnamaLengkap.setText(namaLengkap);
        TVemployeeId.setText(userId != 0 ? String.valueOf(userId) : "ID tidak tersedia");

        // Inisialisasi TextInputEditText untuk kode absensi
        codeInput = view.findViewById(R.id.codeInput);


        Log.d("AbsenFragment", "Nama Lengkap: " + namaLengkap);
        Log.d("AbsenFragment", "User ID: " + userId);

        // Menginisialisasi scanButton1 dengan view yang diinflasi
        scanButton1 = view.findViewById(R.id.scanButton);

        // Mengatur refresh rate ke 120Hz jika perangkat mendukung
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R && getActivity() != null) {
            WindowManager.LayoutParams layoutParams = getActivity().getWindow().getAttributes();
            layoutParams.preferredDisplayModeId = findPreferredDisplayMode(120f);
            getActivity().getWindow().setAttributes(layoutParams);
        }

        // Menambahkan listener untuk scanButton1
        scanButton1.setOnClickListener(v -> Scanner());

        return view; // Mengembalikan view yang diinflate
    }

    private void Scanner() {
        // Membuat opsi pemindaian QR
        ScanOptions options = new ScanOptions();
        options.setPrompt("Volume up to flash on");
        options.setBeepEnabled(true);
        options.setOrientationLocked(true);
        options.setCaptureActivity(StartScan.class);
        launcher.launch(options); // Meluncurkan pemindaian
    }

    private int findPreferredDisplayMode(float targetRefreshRate) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R && getActivity() != null) {
            WindowManager windowManager = getActivity().getSystemService(WindowManager.class);
            if (windowManager != null) {
                Display display = windowManager.getDefaultDisplay();
                if (display != null) {
                    Display.Mode[] supportedModes = display.getSupportedModes();
                    for (Display.Mode mode : supportedModes) {
                        if (Math.abs(mode.getRefreshRate() - targetRefreshRate) < 0.1f) {
                            return mode.getModeId();
                        }
                    }
                }
            }
        }
        return 0;
    }
}
