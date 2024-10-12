package com.example.hadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;
import com.example.hadir.R;
import com.example.hadir.adapter.PengumumanAdapter;
import com.example.hadir.model.Pengumuman;
import java.util.ArrayList;
import java.util.List;

public class BerandaFragment extends Fragment {

    private TextView tvNama, tvJabatan, tvKehadiranBulanIni;
    private RecyclerView rvPengumumanTerbaru;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_beranda, container, false);

        tvNama = view.findViewById(R.id.tv_nama);
        tvJabatan = view.findViewById(R.id.tv_jabatan);
        tvKehadiranBulanIni = view.findViewById(R.id.tv_kehadiran_bulan_ini);
        rvPengumumanTerbaru = view.findViewById(R.id.rv_pengumuman_terbaru);

        return view;
    }

    @Override
    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        // Simulasi data pengguna
        tvNama.setText("RANOVE");
        tvJabatan.setText("Claude Lovers");
        tvKehadiranBulanIni.setText("Kehadiran Bulan Ini: 18/22 hari");

        // Simulasi data pengumuman
        List<Pengumuman> pengumumanList = new ArrayList<>();
        pengumumanList.add(new Pengumuman("Rapat Bulanan", "Rapat bulanan akan diadakan pada tanggal 30 Juli 2023", "30 Juli 2023", "Admin"));
        pengumumanList.add(new Pengumuman("Libur Nasional", "Tanggal 17 Agustus 2023 adalah hari libur nasional", "17 Agustus 2023", "Admin"));


        PengumumanAdapter adapter = new PengumumanAdapter(pengumumanList);
        rvPengumumanTerbaru.setAdapter(adapter);
    }
}