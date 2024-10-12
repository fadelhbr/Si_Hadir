package com.example.hadir.fragment;

import android.Manifest;
import android.content.pm.PackageManager;
import android.location.Location;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.TextView;
import androidx.core.app.ActivityCompat;
import androidx.fragment.app.Fragment;
import com.example.hadir.R;

public class AbsenFragment extends Fragment {

    private Button btnAbsen;
    private TextView tvStatus, tvLokasi;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_absen, container, false);

        btnAbsen = view.findViewById(R.id.btn_absen);
        tvStatus = view.findViewById(R.id.tv_status);
        tvLokasi = view.findViewById(R.id.tv_lokasi);

        return view;
    }

    @Override
    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        btnAbsen.setOnClickListener(v -> prosesAbsen());
    }

    private void prosesAbsen() {
        if (ActivityCompat.checkSelfPermission(requireContext(), Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(requireActivity(), new String[]{Manifest.permission.ACCESS_FINE_LOCATION}, 1);
            return;
        }

    }

    }
