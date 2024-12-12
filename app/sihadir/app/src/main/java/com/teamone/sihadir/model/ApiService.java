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

    @POST("api_send_otp.php")
    Call<ApiResponse> sendOtp(@Body SendOtpRequest request);

    @POST("api_verify_otp.php")
    Call<ApiResponse> verifyOtp(@Body OtpVerificationRequest request);
    @POST("api_reset_password.php")
    Call<ApiResponse> resetPassword(@Body ResetPasswordRequest resetPasswordRequest);

    @POST("api_riwayat_perizinan.php")
    Call<RiwayatIzinResponse> getRiwayatIzin(@Body JsonObject body);

    @POST("api_riwayat_cuti.php")  // Replace with your actual API endpoint
    Call<RiwayatCutiResponse> getRiwayatCuti(@Body JsonObject body);

    @POST("api_cuti.php")
    Call<CutiResponse> submitLeaveRequest(@Body CutiRequest cutiRequest);

    @POST("api_perizinan.php")
    Call<IzinResponse> submitIzinRequest(@Body IzinRequest izinRequest);

}


