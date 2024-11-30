package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;

import com.teamone.sihadir.R;

public class BerandaFragment extends Fragment {

    private TextView tvNama, tvRole, tvKehadiranBulanIni;
    private RecyclerView rvPengumumanTerbaru;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_beranda, container, false);

        tvNama = view.findViewById(R.id.tv_nama);
        if (tvNama != null){
            Log.d("BerandaFragment", "tvNama berhasil");
        } else {
            Log.d("BerandaFragment", "tvNama tidak ditemukan");
        }

        tvRole = view.findViewById(R.id.tv_role);
        tvKehadiranBulanIni = view.findViewById(R.id.tv_kehadiran_bulan_ini);
        rvPengumumanTerbaru = view.findViewById(R.id.rv_pengumuman_terbaru);

        return view;
    }

    @Override
    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        if (getArguments() !=null){
            String nama_lengkap = getArguments().getString("nama_lengkap");
            String role = getArguments().getString("role");
            Log.d("BerandaFragment", "Nama diterima di Fragment: " + nama_lengkap);
            if(nama_lengkap != null){
                tvNama.setText(nama_lengkap);
                tvRole.setText(role);
            }
        } else {
            Log.d("BerandaFragment", "Nama tidak diterima");
        }
        tvKehadiranBulanIni.setText("Kehadiran Bulan Ini: 18/22 hari");

    }
}