package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;

import com.teamone.sihadir.R;
import com.teamone.sihadir.adapter.RiwayatKehadiranAdapter;
import com.teamone.sihadir.model.Kehadiran;

import java.util.ArrayList;
import java.util.Date;
import java.util.List;

public class RiwayatFragment extends Fragment {

    private RecyclerView rvRiwayatKehadiran;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_riwayat, container, false);

        rvRiwayatKehadiran = view.findViewById(R.id.rv_riwayat_kehadiran);

        return view;
    }

    @Override
    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        // Simulasi data riwayat kehadiran
        List<Kehadiran> kehadiranList = new ArrayList<>();
        kehadiranList.add(new Kehadiran(new Date(), "Hadir", "09:00", "17:00"));
        kehadiranList.add(new Kehadiran(new Date(System.currentTimeMillis() - 86400000), "Sakit", "-", "-"));
        kehadiranList.add(new Kehadiran(new Date(System.currentTimeMillis() - 172800000), "Hadir", "08:55", "17:05"));

        RiwayatKehadiranAdapter adapter = new RiwayatKehadiranAdapter(kehadiranList);
        rvRiwayatKehadiran.setAdapter(adapter);
    }
}