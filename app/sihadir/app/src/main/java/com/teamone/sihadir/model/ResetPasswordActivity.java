package com.teamone.sihadir.model;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import com.teamone.sihadir.LoginActivity;
import com.teamone.sihadir.R;

import java.io.IOException;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ResetPasswordActivity extends AppCompatActivity {
    private static final String TAG = "ResetPasswordActivity";

    private TextInputEditText etNewPassword, etConfirmPassword;
    private MaterialButton btnResetPassword;
    private ProgressBar progressBarReset;

    private String otpCode;
    private String email;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_reset_password);

        etNewPassword = findViewById(R.id.et_new_password);
        etConfirmPassword = findViewById(R.id.et_confirm_password);
        btnResetPassword = findViewById(R.id.btn_reset_password);
        progressBarReset = findViewById(R.id.progressBarReset);

        Intent intent = getIntent();
        email = intent.getStringExtra("email");
        otpCode = intent.getStringExtra("otp_code");

        Log.d(TAG, "Received Email: " + email);
        Log.d(TAG, "Received OTP: " + otpCode);

        btnResetPassword.setOnClickListener(v -> resetPassword());
    }

    private void resetPassword() {
        String newPassword = etNewPassword.getText().toString().trim();
        String confirmPassword = etConfirmPassword.getText().toString().trim();

        if (newPassword.isEmpty() || confirmPassword.isEmpty()) {
            Toast.makeText(this, "Mohon isi semua kolom", Toast.LENGTH_SHORT).show();
            return;
        }

        if (!newPassword.equals(confirmPassword)) {
            Toast.makeText(this, "Password dan konfirmasi password tidak cocok", Toast.LENGTH_SHORT).show();
            return;
        }

        Log.d(TAG, "Reset Password Attempt:");
        Log.d(TAG, "Email: " + email);
        Log.d(TAG, "OTP Code: " + otpCode);

        progressBarReset.setVisibility(View.VISIBLE);

        ResetPasswordRequest request = new ResetPasswordRequest(email, otpCode, newPassword);

        Log.d(TAG, "Request Object:");
        Log.d(TAG, "Request Email: " + request.getEmail());
        Log.d(TAG, "Request OTP Code: " + request.getOtp_code());
        Log.d(TAG, "Request New Password Length: " + request.getNew_password().length());

        ApiService apiService = RetrofitClient.getClient().create(ApiService.class);
        Call<ApiResponse> call = apiService.resetPassword(request);

        call.enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                progressBarReset.setVisibility(View.GONE);

                Log.d(TAG, "Response Code: " + response.code());
                if (response.body() != null) {
                    Log.d(TAG, "Response Body: " + response.body().toString());
                }

                if (response.isSuccessful() && response.body() != null) {
                    ApiResponse apiResponse = response.body();
                    if (apiResponse.isSuccess()) {
                        Toast.makeText(ResetPasswordActivity.this, "Password berhasil direset", Toast.LENGTH_SHORT).show();

                        // Navigasi ke LoginActivity
                        Intent intent = new Intent(ResetPasswordActivity.this, LoginActivity.class);
                        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
                        startActivity(intent);
                        finish(); // Akhiri aktivitas saat ini
                    } else {
                        Log.e(TAG, "Reset Failed: " + apiResponse.getMessage());
                        Toast.makeText(ResetPasswordActivity.this, "OTP tidak valid atau sudah kadaluarsa", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    try {
                        String errorBody = response.errorBody() != null ? response.errorBody().string() : "Unknown error";
                        Log.e(TAG, "Error Body: " + errorBody);
                    } catch (IOException e) {
                        Log.e(TAG, "Error parsing error body", e);
                    }
                    Toast.makeText(ResetPasswordActivity.this, "Terjadi kesalahan saat reset password", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                progressBarReset.setVisibility(View.GONE);
                Log.e(TAG, "Network Failure", t);
                Toast.makeText(ResetPasswordActivity.this, "Gagal menghubungi server", Toast.LENGTH_SHORT).show();
            }
        });
    }
}
