package com.teamone.sihadir.fragment;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Base64;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.MotionEvent;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.preference.PreferenceManager;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.google.android.material.progressindicator.LinearProgressIndicator;
import com.teamone.sihadir.R;
import com.teamone.sihadir.adapter.RiwayatAdapter;
import com.teamone.sihadir.model.ApiResponse;
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.RetrofitClient;
import com.teamone.sihadir.model.Riwayat;

import java.io.IOException;
import java.util.Calendar;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import retrofit2.Retrofit;

public class RiwayatFragment extends Fragment {
    private RecyclerView tabelRiwayat;
    private RiwayatAdapter riwayatAdapter;

    // SharedPreferences keys
    private static final String PREF_IS_LOGGED_IN = "is_logged_in";
    private static final String PREF_USERNAME = "username";
    private static final String PREF_PASSWORD = "password";
    private static final String PREF_LAST_MONTH = "last_month";  // Store last checked month

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // Verify credentials and check for month change
        verifyCredentials();
        checkMonthChange();
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_riwayat, container, false);

        tabelRiwayat = view.findViewById(R.id.rvTabelRiwayat);
        tabelRiwayat.setLayoutManager(new LinearLayoutManager(getContext()));


        tabelRiwayat.setOnTouchListener((v, event) -> {
            switch (event.getAction()){
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

        // Only fetch data if credentials are verified
        if (verifyCredentials()) {
            fetchData();
        }

        return view;
    }

    private boolean verifyCredentials() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(requireContext());

        boolean isLoggedIn = preferences.getBoolean(PREF_IS_LOGGED_IN, false);
        String username = preferences.getString(PREF_USERNAME, "");
        String password = preferences.getString(PREF_PASSWORD, "");

        Log.d("RIWAYAT_VERIFY", "Is Logged In: " + isLoggedIn);
        Log.d("RIWAYAT_VERIFY", "Username present: " + !username.isEmpty());
        Log.d("RIWAYAT_VERIFY", "Password present: " + !password.isEmpty());

        return isLoggedIn && !username.isEmpty() && !password.isEmpty();
    }

    private String decryptPassword(String encryptedPassword) {
        try {
            byte[] decodedBytes = Base64.decode(encryptedPassword, Base64.DEFAULT);
            return new String(decodedBytes);
        } catch (Exception e) {
            Log.e("DECRYPT_ERROR", "Error decrypting password", e);
            return "";
        }
    }

    private void checkMonthChange() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(requireContext());
        int lastMonth = preferences.getInt(PREF_LAST_MONTH, -1); // Default to -1 if not found

        Calendar calendar = Calendar.getInstance();
        int currentMonth = calendar.get(Calendar.MONTH);

        // Check if the month has changed
        if (lastMonth != currentMonth) {
            // Update the stored month
            SharedPreferences.Editor editor = preferences.edit();
            editor.putInt(PREF_LAST_MONTH, currentMonth);
            editor.apply();

            // Fetch new data for the new month
            fetchData();
        }
    }

    private void fetchData() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(requireContext());

        String username = preferences.getString(PREF_USERNAME, "");
        String encryptedPassword = preferences.getString(PREF_PASSWORD, "");

        if (username.isEmpty() || encryptedPassword.isEmpty()) {
            Log.e("RIWAYAT_ERROR", "Username or password missing");
            Toast.makeText(getContext(), "Kredensial tidak lengkap, silakan login ulang", Toast.LENGTH_SHORT).show();
            return;
        }

        String password = decryptPassword(encryptedPassword);
        if (password.isEmpty()) {
            Log.e("RIWAYAT_ERROR", "Failed to decrypt password");
            return;
        }

        Log.d("RIWAYAT_DEBUG", "Making API call with username: " + username);

        // Membuat body parameter
        Map<String, String> body = new HashMap<>();
        body.put("username", username);
        body.put("password", password);

        Retrofit retrofit = RetrofitClient.getClient();
        ApiService apiService = retrofit.create(ApiService.class);

        apiService.getRiwayatKehadiran(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                if (response.isSuccessful()) {
                    List<Riwayat> riwayatList = response.body().getData();
                    if (riwayatList != null && !riwayatList.isEmpty()) {
                        // Membalikkan urutan untuk menampilkan data terbaru
                        Collections.reverse(riwayatList);

                        RiwayatAdapter adapter = new RiwayatAdapter(riwayatList);
                        tabelRiwayat.setAdapter(adapter);

                        // Mendapatkan total hari dalam bulan saat ini
                        Calendar calendar = Calendar.getInstance();
                        int currentDayOfMonth = calendar.get(Calendar.DAY_OF_MONTH);
                        int totalDaysInMonth = calendar.getActualMaximum(Calendar.DAY_OF_MONTH);

                        // Menghitung Total Hari Kerja dan Persentase Kehadiran
                        int totalHariKerja = 0;
                        int totalHadir = 0;
                        int totalCuti = 0;
                        int totalPulangAwal = 0;
                        int totalDaysRecorded = 0;  // Jumlah hari yang sudah tercatat kehadirannya

                        for (Riwayat riwayat : riwayatList) {
                            String status = riwayat.getStatusKehadiran();

                            // Menghitung hari kerja berdasarkan status yang dianggap hari kerja
                            if (status.equals("hadir") || status.equals("terlambat") ||
                                    status.equals("tidak_absen_pulang") || status.equals("pulang_dahulu")) {
                                totalHariKerja++;  // Menambah total hari kerja
                                totalDaysRecorded++;  // Menambah jumlah hari yang tercatat kehadirannya

                                // Menghitung kehadiran berdasarkan status yang dianggap hadir
                                if (status.equals("hadir") || status.equals("terlambat") ||
                                        status.equals("tidak_absen_pulang") || status.equals("pulang_dahulu")) {
                                    totalHadir++;  // Menambah jumlah hadir
                                }
                            }

                            // Menghitung Cuti
                            if (status.equals("cuti")) {
                                totalCuti++;  // Menambah jumlah cuti
                            }

                            // Menghitung Pulang Awal
                            if (status.equals("pulang_dahulu")) {
                                totalPulangAwal++;  // Menambah jumlah pulang awal
                            }
                        }

                        // Menghitung hari yang "Tidak Hadir" berdasarkan hari yang sudah tercatat
                        int totalAbsentDays = currentDayOfMonth - totalDaysRecorded;
                        if (totalAbsentDays < 0) {
                            totalAbsentDays = 0;  // Tidak boleh negatif
                        }

                        // Hitung persentase berdasarkan total hari dalam bulan
                        float persentase = ((float) totalHadir / totalDaysInMonth) * 100;

                        // Menampilkan Total Hari Kerja di TextView
                        TextView totalWorkDays = getActivity().findViewById(R.id.totalWorkDays);
                        totalWorkDays.setText(totalHariKerja + " hari");

                        // Menampilkan persentase di TextView
                        TextView attendancePercentage = getActivity().findViewById(R.id.attendancePercentage);
                        attendancePercentage.setText(String.format("%.2f%% Kehadiran", persentase));

                        // Menampilkan hari "Hadir" di TextView
                        TextView hadirDays = getActivity().findViewById(R.id.presentDays);
                        hadirDays.setText(totalHadir + " hari");

                        // Menampilkan hari "Tidak Hadir" di TextView
                        TextView absentDays = getActivity().findViewById(R.id.absentDays);
                        absentDays.setText(totalAbsentDays + " hari");

                        // Menampilkan hari "Cuti" di TextView
                        TextView leaveDays = getActivity().findViewById(R.id.leaveDays);
                        leaveDays.setText(totalCuti + " hari");

                        // Menampilkan hari "Pulang Awal" di TextView
                        TextView earlyLeaveDays = getActivity().findViewById(R.id.earlyLeaveDays);
                        earlyLeaveDays.setText(totalPulangAwal + " hari");

                        // Update ProgressBar
                        LinearProgressIndicator attendanceProgress = getActivity().findViewById(R.id.attendanceProgress);
                        attendanceProgress.setProgress((int) persentase);

                    } else {
                        Toast.makeText(getContext(), "Data kosong", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    handleApiError(response);
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                Log.e("RIWAYAT_ERROR", "API call failed", t);
                Toast.makeText(getContext(), "Gagal memuat data: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void handleApiError(Response<ApiResponse> response) {
        Log.e("RIWAYAT_ERROR", "API Error: " + response.code());
        if (response.errorBody() != null) {
            try {
                Log.e("RIWAYAT_ERROR", response.errorBody().string());
            } catch (IOException e) {
                e.printStackTrace();
            }
        }
        Toast.makeText(getContext(), "Gagal memuat data: " + response.message(), Toast.LENGTH_SHORT).show();
    }
}

