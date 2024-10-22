package com.example.kay.fragment;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.provider.MediaStore;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;
import androidx.fragment.app.Fragment;

import com.example.kay.R;

import java.io.File;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

public class AbsenFragment extends Fragment {
    private TextView tvStatus, tvLokasi;
    private Uri photoUri;
    private ActivityResultLauncher<Intent> cameraLauncher;
    private ActivityResultLauncher<String> requestPermissionLauncher;
    private static final int PERMISSION_REQUEST_CODE = 123;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_absen, container, false);

        // Initialize views
        Button btnAbsen = view.findViewById(R.id.btn_absen);
        tvStatus = view.findViewById(R.id.tv_status);
        tvLokasi = view.findViewById(R.id.tv_lokasi);

        // Initialize permission launcher
        requestPermissionLauncher = registerForActivityResult(
                new ActivityResultContracts.RequestPermission(),
                isGranted -> {
                    if (isGranted) {
                        // Permission granted, proceed with camera
                        startCamera();
                    } else {
                        Toast.makeText(getActivity(), "Izin kamera diperlukan untuk absensi", Toast.LENGTH_SHORT).show();
                    }
                }
        );

        // Initialize camera launcher
        cameraLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == getActivity().RESULT_OK) {
                        // Photo taken successfully
                        updateStatus("Status: Sudah Absen");
                        updateLocation("Lokasi: [Koordinat GPS]");
                        uploadPhotoToServer();
                    } else {
                        Toast.makeText(getActivity(), "Pengambilan foto dibatalkan", Toast.LENGTH_SHORT).show();
                    }
                }
        );

        btnAbsen.setOnClickListener(v -> checkPermissionAndOpenCamera());

        return view;
    }

    private void checkPermissionAndOpenCamera() {
        if (getActivity() == null) return;

        if (ContextCompat.checkSelfPermission(getActivity(), Manifest.permission.CAMERA)
                != PackageManager.PERMISSION_GRANTED) {
            // Request camera permission
            requestPermissionLauncher.launch(Manifest.permission.CAMERA);
        } else {
            // Permission already granted
            startCamera();
        }
    }

    private void startCamera() {
        if (getActivity() == null) return;

        try {
            Intent cameraIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
            if (cameraIntent.resolveActivity(getActivity().getPackageManager()) != null) {
                File photoFile = createImageFile();
                if (photoFile != null) {
                    photoUri = FileProvider.getUriForFile(
                            getActivity(),
                            "com.example.kay.fileprovider",
                            photoFile
                    );
                    cameraIntent.putExtra(MediaStore.EXTRA_OUTPUT, photoUri);
                    cameraLauncher.launch(cameraIntent);
                } else {
                    Toast.makeText(getActivity(), "Error membuat file foto", Toast.LENGTH_SHORT).show();
                }
            } else {
                Toast.makeText(getActivity(), "Tidak ada aplikasi kamera", Toast.LENGTH_SHORT).show();
            }
        } catch (Exception e) {
            e.printStackTrace();
            Toast.makeText(getActivity(), "Error: " + e.getMessage(), Toast.LENGTH_SHORT).show();
        }
    }

    private File createImageFile() {
        try {
            String timeStamp = new SimpleDateFormat("yyyyMMdd_HHmmss", Locale.getDefault()).format(new Date());
            String imageFileName = "JPEG_" + timeStamp + "_";
            File storageDir = getActivity().getExternalFilesDir(Environment.DIRECTORY_PICTURES);
            if (!storageDir.exists()) {
                storageDir.mkdirs();
            }
            return File.createTempFile(imageFileName, ".jpg", storageDir);
        } catch (Exception e) {
            e.printStackTrace();
            Toast.makeText(getActivity(), "Error membuat file: " + e.getMessage(), Toast.LENGTH_SHORT).show();
            return null;
        }
    }

    private void updateStatus(String status) {
        if (tvStatus != null) {
            tvStatus.setText(status);
        }
    }

    private void updateLocation(String location) {
        if (tvLokasi != null) {
            tvLokasi.setText(location);
        }
    }

    private void uploadPhotoToServer() {
        if (getActivity() != null) {
            Toast.makeText(getActivity(), "Foto berhasil diambil dan siap untuk diupload", Toast.LENGTH_SHORT).show();
        }
    }
}