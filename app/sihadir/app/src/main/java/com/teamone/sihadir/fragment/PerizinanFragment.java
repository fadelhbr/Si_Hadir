package com.teamone.sihadir.fragment;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.button.MaterialButton;
import com.google.gson.JsonObject;
import com.teamone.sihadir.R;
import com.teamone.sihadir.adapter.RiwayatCutiAdapter;
import com.teamone.sihadir.adapter.RiwayatIzinAdapter;
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.RetrofitClient;
import com.teamone.sihadir.model.RiwayatCuti;
import com.teamone.sihadir.model.RiwayatCutiResponse;
import com.teamone.sihadir.model.RiwayatIzin;
import com.teamone.sihadir.model.RiwayatIzinResponse;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class PerizinanFragment extends Fragment implements
        DialogFormPerizinan.OnPermissionSubmitListener,
        DialogFormCuti.OnLeaveSubmitListener {

    private MaterialButton btnAjukanIzin;
    private MaterialButton btnAjukanCuti;
    private int pegawaiId;
    private RecyclerView rvTabelRiwayatIzin;
    private RecyclerView rvTabelRiwayatCuti;
    private SwipeRefreshLayout swipeRefreshLayout;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_perizinan, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        // Inisialisasi SwipeRefreshLayout
        swipeRefreshLayout = view.findViewById(R.id.swipeRefreshLayout);

        // Inisialisasi tombol dan RecyclerView
        btnAjukanIzin = view.findViewById(R.id.btnAjukanIzin);
        btnAjukanCuti = view.findViewById(R.id.btnAjukanCuti);
        rvTabelRiwayatIzin = view.findViewById(R.id.rvTabelRiwayatIzin);
        rvTabelRiwayatCuti = view.findViewById(R.id.rvTabelRiwayatCuti);

        // Ambil pegawai_id dari SharedPreferences
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(requireContext());
        pegawaiId = preferences.getInt("pegawai_id", -1);

        // Set klik listener untuk tombol
        btnAjukanIzin.setOnClickListener(v -> showPermissionForm());
        btnAjukanCuti.setOnClickListener(v -> showLeaveForm());

        // Setup RecyclerView
        setupRecyclerViews();

        // Set listener untuk swipe refresh
        swipeRefreshLayout.setOnRefreshListener(() -> {
            // Saat swipe refresh, ambil data baru
            if (pegawaiId != -1) {
                fetchRiwayatIzinData((RiwayatIzinAdapter) rvTabelRiwayatIzin.getAdapter());
                fetchRiwayatCutiData((RiwayatCutiAdapter) rvTabelRiwayatCuti.getAdapter());
            } else {
                Log.e("PerizinanFragment", "pegawaiId belum diinisialisasi atau kosong!");
            }
        });
    }

    private void setupRecyclerViews() {
        // Setup RecyclerView untuk Riwayat Izin
        rvTabelRiwayatIzin.setLayoutManager(new LinearLayoutManager(getContext()));
        RiwayatIzinAdapter izinAdapter = new RiwayatIzinAdapter(new ArrayList<>());
        rvTabelRiwayatIzin.setAdapter(izinAdapter);

        // Setup RecyclerView untuk Riwayat Cuti
        rvTabelRiwayatCuti.setLayoutManager(new LinearLayoutManager(getContext()));
        RiwayatCutiAdapter cutiAdapter = new RiwayatCutiAdapter(new ArrayList<>());
        rvTabelRiwayatCuti.setAdapter(cutiAdapter);

        // Panggil API untuk mengambil data izin dan cuti
        if (pegawaiId != -1) {
            fetchRiwayatIzinData(izinAdapter);
            fetchRiwayatCutiData(cutiAdapter);
        } else {
            Log.e("PerizinanFragment", "pegawaiId belum diinisialisasi atau kosong!");
        }
    }

    private void fetchRiwayatIzinData(RiwayatIzinAdapter adapter) {
        ApiService apiService = RetrofitClient.getClient().create(ApiService.class);

        JsonObject requestBody = new JsonObject();
        requestBody.addProperty("pegawai_id", pegawaiId);

        Call<RiwayatIzinResponse> call = apiService.getRiwayatIzin(requestBody);
        call.enqueue(new Callback<RiwayatIzinResponse>() {
            @Override
            public void onResponse(Call<RiwayatIzinResponse> call, Response<RiwayatIzinResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    RiwayatIzinResponse riwayatIzinResponse = response.body();
                    List<RiwayatIzin> riwayatIzinList = riwayatIzinResponse.getData();
                    adapter.updateData(riwayatIzinList);  // Update RecyclerView dengan data baru
                } else {
                    Log.e("PerizinanFragment", "Response tidak berhasil");
                }
                // Stop refresh animation after data is loaded
                swipeRefreshLayout.setRefreshing(false);
            }

            @Override
            public void onFailure(Call<RiwayatIzinResponse> call, Throwable t) {
                Log.e("PerizinanFragment", "Request gagal: " + t.getMessage());
                // Stop refresh animation on failure
                swipeRefreshLayout.setRefreshing(false);
            }
        });
    }

    private void fetchRiwayatCutiData(RiwayatCutiAdapter adapter) {
        ApiService apiService = RetrofitClient.getClient().create(ApiService.class);

        JsonObject requestBody = new JsonObject();
        requestBody.addProperty("pegawai_id", pegawaiId);

        Call<RiwayatCutiResponse> call = apiService.getRiwayatCuti(requestBody);
        call.enqueue(new Callback<RiwayatCutiResponse>() {
            @Override
            public void onResponse(Call<RiwayatCutiResponse> call, Response<RiwayatCutiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    RiwayatCutiResponse riwayatCutiResponse = response.body();
                    List<RiwayatCuti> riwayatCutiList = riwayatCutiResponse.getData();
                    adapter.updateData(riwayatCutiList);  // Update RecyclerView dengan data baru
                } else {
                    Log.e("PerizinanFragment", "Response tidak berhasil");
                }
                // Stop refresh animation after data is loaded
                swipeRefreshLayout.setRefreshing(false);
            }

            @Override
            public void onFailure(Call<RiwayatCutiResponse> call, Throwable t) {
                Log.e("PerizinanFragment", "Request gagal: " + t.getMessage());
                // Stop refresh animation on failure
                swipeRefreshLayout.setRefreshing(false);
            }
        });
    }


    private void showPermissionForm() {
        if (pegawaiId != -1) {
            DialogFormPerizinan dialog = DialogFormPerizinan.newInstance(pegawaiId);
            dialog.setOnPermissionSubmitListener(this);
            dialog.show(getChildFragmentManager(), "PermissionFormDialog");
        } else {
            Log.e("PerizinanFragment", "pegawaiId belum diinisialisasi atau kosong!");
        }
    }

    private void showLeaveForm() {
        if (pegawaiId != -1) {
            DialogFormCuti dialog = DialogFormCuti.newInstance(pegawaiId);
            dialog.setOnLeaveSubmitListener(this);
            dialog.show(getChildFragmentManager(), "CutiFormDialog");
        } else {
            Log.e("PerizinanFragment", "pegawaiId belum diinisialisasi atau kosong!");
        }
    }

    @Override
    public void onPermissionSubmitted(String jenisIzin, String tanggal, String keterangan) {
        // Menyegarkan data riwayat izin
        refreshPermissionHistory();
    }

    @Override
    public void onLeaveSubmitted(String startDate, String endDate, String keterangan, int pegawaiId) {
        // Menyegarkan data riwayat cuti
        refreshLeaveHistory();
    }

    private void refreshPermissionHistory() {
        // Perbarui data riwayat izin
        fetchRiwayatIzinData((RiwayatIzinAdapter) rvTabelRiwayatIzin.getAdapter());
    }

    private void refreshLeaveHistory() {
        // Perbarui data riwayat cuti
        fetchRiwayatCutiData((RiwayatCutiAdapter) rvTabelRiwayatCuti.getAdapter());
    }
}