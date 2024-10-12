package com.example.hadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;
import com.example.hadir.R;
import com.example.hadir.adapter.PengumumanAdapter;
import com.example.hadir.model.Pengumuman;
import java.util.ArrayList;
import java.util.List;

public class PengumumanFragment extends Fragment {

    private RecyclerView rvPengumuman;
    private SwipeRefreshLayout swipeRefreshLayout;
    private PengumumanAdapter adapter;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_pengumuman, container, false);

        rvPengumuman = view.findViewById(R.id.rv_pengumuman);
        swipeRefreshLayout = view.findViewById(R.id.swipe_refresh);

        return view;
    }

    @Override
    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        List<Pengumuman> pengumumanList = new ArrayList<>();
        adapter = new PengumumanAdapter(pengumumanList);
        rvPengumuman.setAdapter(adapter);

        swipeRefreshLayout.setOnRefreshListener(this::refreshData);

        loadPengumuman();
    }

    private void loadPengumuman() {
        List<Pengumuman> pengumumanList = new ArrayList<>();
        pengumumanList.add(new Pengumuman("Rapat Tahunan", "Rapat tahunan akan diadakan pada tanggal 15 Desember 2023", "15 Desember 2023", "Admin"));
        pengumumanList.add(new Pengumuman("Pelatihan Baru", "Pelatihan pengembangan soft skill akan diadakan minggu depan", "10 Oktober 2023", "HRD"));
        pengumumanList.add(new Pengumuman("Maintenance Sistem", "Sistem akan mengalami pemeliharaan pada tanggal 20 Juli 2023", "20 Juli 2023", "IT Support"));

        adapter.updateData(pengumumanList);
    }


    private void refreshData() {
        // Simulasi refresh data
        loadPengumuman();
        swipeRefreshLayout.setRefreshing(false);
    }
}