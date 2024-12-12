
package com.teamone.sihadir.activity;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.teamone.sihadir.R;
import com.teamone.sihadir.model.ApiResponse;
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.RetrofitClient;
import com.teamone.sihadir.model.SendOtpRequest;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class SendOtpActivity extends AppCompatActivity {

    private EditText emailInput;
    private Button btnSend; // Tombol utama untuk mengirim OTP
    private ProgressBar progressBar;
    private TextView resendOtp; // TextView untuk "Kirim ulang kode OTP"

    @SuppressLint("MissingInflatedId")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_send_otp);

        emailInput = findViewById(R.id.email_input);
        btnSend = findViewById(R.id.btnSend);
        progressBar = findViewById(R.id.progressBarSend);
        resendOtp = findViewById(R.id.ResendOTP); // Inisialisasi TextView ResendOTP

        // Listener untuk tombol btnSend
        btnSend.setOnClickListener(view -> sendOtp());

    }

    private void sendOtp() {
        String email = emailInput.getText().toString().trim();
        if (isValidEmail(email)) {
            sendOtpToEmail(email); // Memanggil fungsi sendOtpToEmail
        } else {
            Toast.makeText(this, "Email tidak valid", Toast.LENGTH_SHORT).show();
        }
    }

    private void sendOtpToEmail(String email) {
        progressBar.setVisibility(View.VISIBLE);

        SendOtpRequest request = new SendOtpRequest(email);

        ApiService apiService = RetrofitClient.getClient().create(ApiService.class);
        Call<ApiResponse> call = apiService.sendOtp(request);

        call.enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null) {
                    ApiResponse apiResponse = response.body();
                    if (apiResponse.isSuccess()) {
                        Intent intent = new Intent(SendOtpActivity.this, VerifyOtpActivity.class);
                        intent.putExtra("email", email);
                        startActivity(intent);
                    } else {
                        Toast.makeText(SendOtpActivity.this, "Gagal mengirim OTP", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    Toast.makeText(SendOtpActivity.this, "Terjadi kesalahan", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(SendOtpActivity.this, "Tidak dapat terhubung ke server", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private boolean isValidEmail(String email) {
        return android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches();
    }
}