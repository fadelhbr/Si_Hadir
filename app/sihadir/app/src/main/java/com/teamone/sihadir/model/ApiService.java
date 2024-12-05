package com.teamone.sihadir.model;

import com.google.gson.JsonObject;

import java.util.Map;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.GET;
import retrofit2.http.POST;
import retrofit2.http.PUT;
import retrofit2.http.Query;

public interface ApiService {
    @FormUrlEncoded
    @POST("api_login.php")
        // sesuaikan dengan path API kamu
    Call<ApiResponse> loginUser(
            @Field("username") String username,
            @Field("password") String password,
            @Field("nama_lengkap") String nama_lengkap,
            @Field("employee_id") String employee_id
    );

    @POST("api_riwayat.php") // Pastikan endpoint ini menerima data melalui POST
    Call<ApiResponse> getRiwayatKehadiran(
            @Body Map<String, String> body // Mengirimkan username dan password melalui body
    );

    @POST("api_attendance.php") // Sesuaikan dengan endpoint API Anda
    Call<AbsensiApiResponse> submitAbsensi(@Body AbsensiRequest request);

    @GET("api_schedule.php")
    Call<ScheduleResponse> getEmployeeSchedule(@Query("user_id") int userId);

    @GET("api_status.php")
    Call<AttendanceStatusResponse> getAttendanceStatus(@Query("user_id") int userId);

}
