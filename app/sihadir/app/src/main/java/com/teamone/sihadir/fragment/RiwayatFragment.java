package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.MotionEvent;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.ViewModelProvider;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.progressindicator.LinearProgressIndicator;
import com.teamone.sihadir.R;
import com.teamone.sihadir.adapter.RiwayatAdapter;
import com.teamone.sihadir.model.Riwayat;
import com.teamone.sihadir.viewmodel.RiwayatViewModel;

import java.util.ArrayList;

public class RiwayatFragment extends Fragment {

    private RecyclerView tabelRiwayat;
    private RiwayatAdapter riwayatAdapter;
    private RiwayatViewModel riwayatViewModel;
    private SwipeRefreshLayout swipeRefreshLayout;

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        riwayatViewModel = new ViewModelProvider(this).get(RiwayatViewModel.class);
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_riwayat, container, false);

        // Initialize SwipeRefreshLayout
        swipeRefreshLayout = view.findViewById(R.id.swipeRefreshLayout);

        tabelRiwayat = view.findViewById(R.id.rvTabelRiwayat);
        tabelRiwayat.setLayoutManager(new LinearLayoutManager(getContext()));

        // Initialize adapter with empty list
        riwayatAdapter = new RiwayatAdapter(new ArrayList<>());
        tabelRiwayat.setAdapter(riwayatAdapter);

        // Set up SwipeRefreshLayout listener
        swipeRefreshLayout.setOnRefreshListener(() -> {
            // Fetch data again
            riwayatViewModel.fetchRiwayatData();
        });

        tabelRiwayat.setOnTouchListener((v, event) -> {
            switch (event.getAction()) {
                case MotionEvent.ACTION_DOWN:
                case MotionEvent.ACTION_MOVE:
                    v.getParent().requestDisallowInterceptTouchEvent(true);
                    break;
                case MotionEvent.ACTION_UP:
                    v.getParent().requestDisallowInterceptTouchEvent(false);
                    break;
            }
            return false;
        });

        // Observe ViewModel data
        observeViewModel(view);

        return view;
    }

    private void observeViewModel(View view) {
        // Observe Riwayat List
        riwayatViewModel.getRiwayatList().observe(getViewLifecycleOwner(), riwayatList -> {
            if (riwayatList != null) {
                riwayatAdapter = new RiwayatAdapter(riwayatList);
                tabelRiwayat.setAdapter(riwayatAdapter);
            }
        });

        // Modify the loading state observer to control SwipeRefreshLayout
        riwayatViewModel.getIsLoading().observe(getViewLifecycleOwner(), isLoading -> {
            if (swipeRefreshLayout != null) {
                swipeRefreshLayout.setRefreshing(isLoading);
            }
        });

        // Observe Error Messages
        riwayatViewModel.getErrorMessage().observe(getViewLifecycleOwner(), errorMsg -> {
            if (errorMsg != null && !errorMsg.isEmpty()) {
                Toast.makeText(getContext(), errorMsg, Toast.LENGTH_SHORT).show();
            }
        });

        // Observe Statistics
        riwayatViewModel.getTotalWorkDays().observe(getViewLifecycleOwner(), totalWorkDays ->
                ((TextView) view.findViewById(R.id.totalWorkDays)).setText(totalWorkDays + " hari"));

        riwayatViewModel.getPresentDays().observe(getViewLifecycleOwner(), presentDays ->
                ((TextView) view.findViewById(R.id.presentDays)).setText(presentDays + " hari"));

        riwayatViewModel.getLeaveDays().observe(getViewLifecycleOwner(), leaveDays ->
                ((TextView) view.findViewById(R.id.leaveDays)).setText(leaveDays + " hari"));

        riwayatViewModel.getEarlyLeaveDays().observe(getViewLifecycleOwner(), earlyLeaveDays ->
                ((TextView) view.findViewById(R.id.earlyLeaveDays)).setText(earlyLeaveDays + " hari"));

        riwayatViewModel.getAbsentDays().observe(getViewLifecycleOwner(), absentDays ->
                ((TextView) view.findViewById(R.id.absentDays)).setText(absentDays + " hari"));

        riwayatViewModel.getAttendancePercentage().observe(getViewLifecycleOwner(), attendancePercentage -> {
            ((TextView) view.findViewById(R.id.attendancePercentage)).setText(String.format("%.2f%% Kehadiran", attendancePercentage));
            ((LinearProgressIndicator) view.findViewById(R.id.attendanceProgress)).setProgress(attendancePercentage.intValue());
        });
    }
}