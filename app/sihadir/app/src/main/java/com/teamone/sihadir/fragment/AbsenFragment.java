package com.teamone.sihadir.fragment;

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
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.fragment.app.Fragment;
import androidx.preference.PreferenceManager;

import com.journeyapps.barcodescanner.ScanContract;
import com.journeyapps.barcodescanner.ScanOptions;
import com.teamone.sihadir.R;
import com.teamone.sihadir.StartScan;
import com.teamone.sihadir.model.AbsensiRequest;
import com.teamone.sihadir.model.AbsensiApiResponse;
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.RetrofitClient;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class AbsenFragment extends Fragment {

    private Button scanButton1;
    private Button submitButton;
    private TextView TVnamaLengkap;
    private TextView TVemployeeId;
    private TextInputEditText codeInput;

    private ApiService apiService;
    private SharedPreferences sharedPreferences;

    private final ActivityResultLauncher<ScanOptions> launcher = registerForActivityResult(new ScanContract(), result -> {
        if (result.getContents() != null) {
            String scannedCode = result.getContents();

            // Validasi panjang kode
            if (scannedCode.length() > 6) {
                // Jika lebih dari 6 digit, tampilkan pesan error
                showErrorDialog("Kode Tidak Valid", "Kode absensi harus 6 digit atau kurang");
            } else {
                codeInput.setText(scannedCode);
                showConfirmationDialog("Scan Berhasil", "Kode absensi telah terisi");
            }
        }
    });

    private void showEarlyLeaveConfirmationDialog(Runnable onConfirm) {
        AlertDialog.Builder builder = new AlertDialog.Builder(requireContext());
        builder.setTitle("Konfirmasi Early Leave");
        builder.setMessage("Apakah Anda yakin ingin melakukan early leave?");
        builder.setPositiveButton("Ya", (dialog, which) -> {
            dialog.dismiss();
            onConfirm.run(); // Jalankan aksi yang ditentukan
        });
        builder.setNegativeButton("Tidak", (dialog, which) -> dialog.dismiss());
        builder.show();
    }


    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        // Menginflate layout fragment dan mengembalikannya
        View view = inflater.inflate(R.layout.fragment_absen, container, false);

        // Inisialisasi Retrofit API Service
        apiService = RetrofitClient.getClient().create(ApiService.class);

        // Inisialisasi SharedPreferences
        sharedPreferences = PreferenceManager.getDefaultSharedPreferences(requireContext());

        // Inisialisasi TextView
        TVnamaLengkap = view.findViewById(R.id.userName);
        TVemployeeId = view.findViewById(R.id.statusChip);

        // Ambil data dari SharedPreferences
        String namaLengkap = sharedPreferences.getString("nama_lengkap", "Nama tidak tersedia");
        int userId = sharedPreferences.getInt("user_id", 0); // Default 0 jika tidak ditemukan
        Log.d("AbsenFragment", "user_id dari SharedPreferences: " + userId);

        // Tampilkan data di TextView
        TVnamaLengkap.setText(namaLengkap);
        TVemployeeId.setText(userId != 0 ? String.valueOf(userId) : "ID tidak tersedia");

        // Inisialisasi TextInputEditText untuk kode absensi
        codeInput = view.findViewById(R.id.codeInput);

        // Menginisialisasi scanButton1 dan submitButton
        scanButton1 = view.findViewById(R.id.scanButton);
        submitButton = view.findViewById(R.id.submitButton);

        // Mengatur refresh rate ke 120Hz jika perangkat mendukung
        setHighRefreshRate();

        // Menambahkan listener untuk scanButton1
        scanButton1.setOnClickListener(v -> Scanner());

        // Menambahkan listener untuk submitButton
        submitButton.setOnClickListener(v -> submitAbsensi());

        return view;
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

    private void submitAbsensi() {
        String absensiCode = codeInput.getText().toString().trim();
        if (absensiCode.isEmpty()) {
            showErrorDialog("Kode Kosong", "Silakan scan atau masukkan kode absensi");
            return;
        }

        int userId = sharedPreferences.getInt("user_id", 0);
        if (userId == 0) {
            showErrorDialog("Error", "User ID tidak ditemukan. Pastikan Anda telah login.");
            return;
        }

        // Deteksi kode untuk early leave
        boolean isEarlyLeave = absensiCode.startsWith("EL"); // Contoh: awalan "EL" untuk early leave

        if (isEarlyLeave) {
            // Tampilkan dialog konfirmasi jika early leave
            showEarlyLeaveConfirmationDialog(() -> sendAbsensiRequest(userId, absensiCode, true));
        } else {
            // Langsung kirim jika bukan early leave
            sendAbsensiRequest(userId, absensiCode, false);
        }
    }

    private void sendAbsensiRequest(int userId, String absensiCode, boolean confirmEarlyLeave) {
        AbsensiRequest request = new AbsensiRequest(
                String.valueOf(userId),
                absensiCode,
                confirmEarlyLeave
        );

        apiService.submitAbsensi(request).enqueue(new Callback<AbsensiApiResponse>() {
            @Override
            public void onResponse(@NonNull Call<AbsensiApiResponse> call, @NonNull Response<AbsensiApiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    AbsensiApiResponse apiResponse = response.body();

                    if ("success".equalsIgnoreCase(apiResponse.getStatus())) {
                        Toast.makeText(requireContext(), apiResponse.getMessage(), Toast.LENGTH_SHORT).show();
                        codeInput.setText(""); // Reset input setelah berhasil
                    } else {
                        showErrorDialog("Gagal", apiResponse.getMessage());
                    }
                } else {
                    showErrorDialog("Gagal", "Tidak dapat mengirim absensi. Silakan coba lagi.");
                }
            }

            @Override
            public void onFailure(@NonNull Call<AbsensiApiResponse> call, @NonNull Throwable t) {
                showErrorDialog("Error", "Terjadi kesalahan: " + t.getMessage());
            }
        });
    }


    private void setHighRefreshRate() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R && getActivity() != null) {
            WindowManager.LayoutParams layoutParams = getActivity().getWindow().getAttributes();
            layoutParams.preferredDisplayModeId = findPreferredDisplayMode(120f);
            getActivity().getWindow().setAttributes(layoutParams);
        }
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

    private void showErrorDialog(String title, String message) {
        AlertDialog.Builder builder = new AlertDialog.Builder(requireContext());
        builder.setTitle(title);
        builder.setMessage(message);
        builder.setPositiveButton("OK", (dialog, which) -> dialog.dismiss());
        builder.show();
    }

    private void showConfirmationDialog(String title, String message) {
        AlertDialog.Builder builder = new AlertDialog.Builder(requireContext());
        builder.setTitle(title);
        builder.setMessage(message);
        builder.setPositiveButton("OK", (dialog, which) -> dialog.dismiss());
        builder.show();
    }
}