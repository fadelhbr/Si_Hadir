package com.teamone.sihadir.fragment;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Switch;
import android.widget.Toast;

import androidx.fragment.app.Fragment;
import androidx.preference.PreferenceManager;

import com.teamone.sihadir.LoginActivity;
import com.teamone.sihadir.R;

public class PengaturanFragment extends Fragment {

    private Switch switchNotifikasi, switchModeMalam;
    private SharedPreferences sharedPreferences;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_pengaturan, container, false);

        switchNotifikasi = view.findViewById(R.id.switch_notifikasi);
        switchModeMalam = view.findViewById(R.id.switch_mode_malam);

        sharedPreferences = PreferenceManager.getDefaultSharedPreferences(requireContext());

        return view;
    }

    @Override
    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        // Load saved preferences
        switchNotifikasi.setChecked(sharedPreferences.getBoolean("notifikasi", true));
        switchModeMalam.setChecked(sharedPreferences.getBoolean("mode_malam", false));

        switchNotifikasi.setOnCheckedChangeListener((buttonView, isChecked) -> {
            sharedPreferences.edit().putBoolean("notifikasi", isChecked).apply();
            updateNotifikasiKeServer(isChecked);
        });

        switchModeMalam.setOnCheckedChangeListener((buttonView, isChecked) -> {
            sharedPreferences.edit().putBoolean("mode_malam", isChecked).apply();
            updateTema(isChecked);
        });

        view.findViewById(R.id.btn_logout).setOnClickListener(v -> logout());
    }

    private void updateNotifikasiKeServer(boolean isActive) {
        // Simulasi update ke server
        Toast.makeText(requireContext(), "Pengaturan notifikasi diperbarui", Toast.LENGTH_SHORT).show();
    }

    private void updateTema(boolean isNightMode) {
        // Implementasi perubahan tema
        // Dalam kasus nyata, Anda mungkin perlu me-restart activity atau menggunakan AppCompatDelegate
        Toast.makeText(requireContext(), isNightMode ? "Mode malam diaktifkan" : "Mode malam dinonaktifkan", Toast.LENGTH_SHORT).show();
    }

    private void logout() {
        Intent intent = new Intent(requireActivity(), LoginActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
        requireActivity().finish();
    }
}