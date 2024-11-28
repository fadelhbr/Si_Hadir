package com.teamone.sihadir.model;

import okhttp3.ResponseBody;
import retrofit2.Call;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.POST;

public interface ApiService {
    @FormUrlEncoded
    @POST("api/api_login.php") // sesuaikan dengan path API kamu
    Call<ApiResponse> loginUser(
            @Field("username") String username,
            @Field("password") String password,
            @Field("nama_lengkap") String nama_lengkap,
            @Field("employee_id") String employee_id
    );
        @POST("send_otp.php")
        @FormUrlEncoded
        Call<ResponseBody> sendOTP(@Field("email") String email);
    }


