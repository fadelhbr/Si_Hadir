package com.teamone.sihadir.viewmodel;

import android.app.Application;
import android.content.SharedPreferences;
import android.util.Base64;
import android.util.Log;

import androidx.annotation.NonNull;
import androidx.lifecycle.AndroidViewModel;
import androidx.lifecycle.LiveData;
import androidx.lifecycle.MutableLiveData;
import androidx.preference.PreferenceManager;

import com.teamone.sihadir.model.ApiResponse;
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.RetrofitClient;
import com.teamone.sihadir.model.Riwayat;

import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import retrofit2.Retrofit;

public class RiwayatViewModel extends AndroidViewModel {
    private MutableLiveData<List<Riwayat>> riwayatList = new MutableLiveData<>();
    private MutableLiveData<Boolean> isLoading = new MutableLiveData<>();
    private MutableLiveData<String> errorMessage = new MutableLiveData<>();

    // Statistic fields as LiveData
    private MutableLiveData<Integer> totalWorkDays = new MutableLiveData<>(0);
    private MutableLiveData<Integer> presentDays = new MutableLiveData<>(0);
    private MutableLiveData<Integer> leaveDays = new MutableLiveData<>(0);
    private MutableLiveData<Integer> earlyLeaveDays = new MutableLiveData<>(0);
    private MutableLiveData<Integer> absentDays = new MutableLiveData<>(0);
    private MutableLiveData<Float> attendancePercentage = new MutableLiveData<>(0f);

    public RiwayatViewModel(@NonNull Application application) {
        super(application);
        // Fetch data automatically when ViewModel is created
        fetchRiwayatData();
    }

    public LiveData<List<Riwayat>> getRiwayatList() {
        return riwayatList;
    }

    public LiveData<Boolean> getIsLoading() {
        return isLoading;
    }

    public LiveData<String> getErrorMessage() {
        return errorMessage;
    }

    // Getters for statistic LiveData
    public LiveData<Integer> getTotalWorkDays() {
        return totalWorkDays;
    }

    public LiveData<Integer> getPresentDays() {
        return presentDays;
    }

    public LiveData<Integer> getLeaveDays() {
        return leaveDays;
    }

    public LiveData<Integer> getEarlyLeaveDays() {
        return earlyLeaveDays;
    }

    public LiveData<Integer> getAbsentDays() {
        return absentDays;
    }

    public LiveData<Float> getAttendancePercentage() {
        return attendancePercentage;
    }

    public void fetchRiwayatData() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(getApplication());
        String username = preferences.getString("username", "");
        String encryptedPassword = preferences.getString("password", "");

        if (username.isEmpty() || encryptedPassword.isEmpty()) {
            errorMessage.setValue("Kredensial tidak lengkap, silakan login ulang");
            return;
        }

        String password = decryptPassword(encryptedPassword);
        if (password.isEmpty()) {
            errorMessage.setValue("Gagal mendekripsi password");
            return;
        }

        isLoading.setValue(true);

        Map<String, String> body = new HashMap<>();
        body.put("username", username);
        body.put("password", password);

        Retrofit retrofit = RetrofitClient.getClient();
        ApiService apiService = retrofit.create(ApiService.class);

        apiService.getRiwayatKehadiran(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                isLoading.setValue(false);

                if (response.isSuccessful() && response.body() != null) {
                    List<Riwayat> data = response.body().getData();
                    if (data != null && !data.isEmpty()) {
                        Collections.reverse(data);
                        riwayatList.setValue(data);
                        calculateStatistics(data);
                    } else {
                        errorMessage.setValue("Data kosong");
                    }
                } else {
                    errorMessage.setValue("Gagal memuat data");
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                isLoading.setValue(false);
                errorMessage.setValue("Gagal memuat data: " + t.getMessage());
            }
        });
    }

    private void calculateStatistics(List<Riwayat> riwayatList) {
        int totalHariKerja = 0;
        int totalHadir = 0;
        int totalCuti = 0;
        int totalPulangAwal = 0;
        int totalAlpha = 0;

        for (Riwayat riwayat : riwayatList) {
            switch (riwayat.getStatusKehadiran()) {
                case "hadir":
                case "terlambat":
                case "tidak_absen_pulang":
                case "pulang_dahulu":
                    totalHariKerja++;
                    totalHadir++;
                    if (riwayat.getStatusKehadiran().equals("pulang_dahulu")) {
                        totalPulangAwal++;
                    }
                    break;
                case "cuti":
                case "izin":
                    totalCuti++;
                    break;
                case "alpha":
                    totalAlpha++;
                    break;
            }
        }

        int totalDaysInMonth = java.util.Calendar.getInstance().getActualMaximum(java.util.Calendar.DAY_OF_MONTH);
        float persentase = ((float) totalHadir / totalDaysInMonth) * 100;

        totalWorkDays.setValue(totalHariKerja);
        presentDays.setValue(totalHadir);
        leaveDays.setValue(totalCuti);
        earlyLeaveDays.setValue(totalPulangAwal);
        absentDays.setValue(totalAlpha);
        attendancePercentage.setValue(persentase);
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
}