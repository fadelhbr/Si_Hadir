package com.example.hadir.model;

import retrofit2.Call;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.POST;

public interface ApiService {
    @FormUrlEncoded
    @POST("login.php") // sesuaikan dengan path API kamu
    Call<ApiResponse> loginUser(
            @Field("username") String username,
            @Field("password") String password
    );
}
