package com.teamone.sihadir.model;

import android.content.Intent;
import android.os.Bundle;
import android.os.CountDownTimer;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.teamone.sihadir.R;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class VerifyOtpActivity extends AppCompatActivity {

    private EditText otpDigit1, otpDigit2, otpDigit3, otpDigit4, otpDigit5, otpDigit6;
    private Button btnVerifikasi;
    private ProgressBar progressBar;
    private String email;
    private TextView resendOtpTextView;
    private CountDownTimer countDownTimer;
    private static final long START_TIME_IN_MILLIS = 60000; // 60 detik
    private long timeLeftInMillis = START_TIME_IN_MILLIS;
    private TextView emailTextView;
    private TextView tvBackToEmail;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_verify_otp);

        email = getIntent().getStringExtra("email");

        otpDigit1 = findViewById(R.id.otp_digit1);
        otpDigit2 = findViewById(R.id.otp_digit2);
        otpDigit3 = findViewById(R.id.otp_digit3);
        otpDigit4 = findViewById(R.id.otp_digit4);
        otpDigit5 = findViewById(R.id.otp_digit5);
        otpDigit6 = findViewById(R.id.otp_digit6);
        btnVerifikasi = findViewById(R.id.BtnVerifikasi);
        progressBar = findViewById(R.id.progressBarVerif);

        emailTextView = findViewById(R.id.email_text);
        if (email != null) {
            emailTextView.setText(email);
        }

        resendOtpTextView = findViewById(R.id.ResendOTP);
        tvBackToEmail = findViewById(R.id.tv_back_to_email);

        // Start the countdown timer
        startCountDownTimer();

        // Handle resend OTP click
        resendOtpTextView.setOnClickListener(view -> {
            if (timeLeftInMillis <= 1000) {  // Time left is less than or equal to 1 second
                sendOtpToEmail();  // Send OTP if time is up
            } else {
                Toast.makeText(VerifyOtpActivity.this, "Tunggu " + timeLeftInMillis / 1000 + " detik lagi", Toast.LENGTH_SHORT).show();
            }
        });

        // Verifikasi OTP
        btnVerifikasi.setOnClickListener(view -> verifyOtp());

        // Set up OTP EditText listeners
        setupOtpEditTextListeners();

        // Handle back to email verification
        tvBackToEmail.setOnClickListener(view -> {
            Intent backToEmailIntent = new Intent(VerifyOtpActivity.this, SendOtpActivity.class);
            backToEmailIntent.putExtra("email", email); // Pass email back to Send OTP activity
            startActivity(backToEmailIntent);
            finish(); // Close current activity
        });
    }

    // Function to start countdown timer
    private void startCountDownTimer() {
        countDownTimer = new CountDownTimer(START_TIME_IN_MILLIS, 1000) {
            @Override
            public void onTick(long millisUntilFinished) {
                timeLeftInMillis = millisUntilFinished;
                if (millisUntilFinished / 1000 > 0) {
                    resendOtpTextView.setText("Kirim ulang kode OTP (" + millisUntilFinished / 1000 + ")");
                }
            }

            @Override
            public void onFinish() {
                resendOtpTextView.setText("Kirim ulang kode OTP");
                resendOtpTextView.setEnabled(true);
            }
        }.start();
    }

    // Function to send OTP to email
    private void sendOtpToEmail() {
        progressBar.setVisibility(View.VISIBLE);

        // Create request to send OTP
        SendOtpRequest request = new SendOtpRequest(email);

        // Call the API to send OTP
        ApiService apiService = RetrofitClient.getClient().create(ApiService.class);
        Call<ApiResponse> call = apiService.sendOtp(request);

        call.enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null) {
                    ApiResponse apiResponse = response.body();
                    if (apiResponse.isSuccess()) {
                        Toast.makeText(VerifyOtpActivity.this, "OTP telah dikirim ulang", Toast.LENGTH_SHORT).show();
                        startCountDownTimer();
                    } else {
                        Toast.makeText(VerifyOtpActivity.this, "Gagal mengirim OTP", Toast.LENGTH_SHORT).show();
                    }
                } else {
                    Toast.makeText(VerifyOtpActivity.this, "Terjadi kesalahan", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(VerifyOtpActivity.this, "Tidak dapat terhubung ke server", Toast.LENGTH_SHORT).show();
            }
        });
    }

    // Function to verify OTP
    private void verifyOtp() {
        String otp = otpDigit1.getText().toString() +
                otpDigit2.getText().toString() +
                otpDigit3.getText().toString() +
                otpDigit4.getText().toString() +
                otpDigit5.getText().toString() +
                otpDigit6.getText().toString();

        if (!otp.isEmpty()) {
            progressBar.setVisibility(View.VISIBLE);
            ApiService apiService = RetrofitClient.getClient().create(ApiService.class);
            Call<ApiResponse> call = apiService.verifyOtp(new OtpVerificationRequest(email, otp));

            call.enqueue(new Callback<ApiResponse>() {
                @Override
                public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                    progressBar.setVisibility(View.GONE);
                    if (response.isSuccessful() && response.body() != null) {
                        ApiResponse apiResponse = response.body();
                        if (apiResponse.isSuccess()) {
                            Toast.makeText(VerifyOtpActivity.this, "Verifikasi berhasil", Toast.LENGTH_SHORT).show();
                            Intent intent = new Intent(VerifyOtpActivity.this, ResetPasswordActivity.class);
                            intent.putExtra("email", email);
                            intent.putExtra("otp_code", otp);
                            startActivity(intent);
                        } else {
                            Toast.makeText(VerifyOtpActivity.this, "OTP tidak valid", Toast.LENGTH_SHORT).show();
                        }
                    } else {
                        Toast.makeText(VerifyOtpActivity.this, "Terjadi kesalahan saat verifikasi", Toast.LENGTH_SHORT).show();
                    }
                }

                @Override
                public void onFailure(Call<ApiResponse> call, Throwable t) {
                    progressBar.setVisibility(View.GONE);
                    Toast.makeText(VerifyOtpActivity.this, "Gagal menghubungi server", Toast.LENGTH_SHORT).show();
                }
            });
        } else {
            Toast.makeText(this, "Masukkan OTP terlebih dahulu", Toast.LENGTH_SHORT).show();
        }
    }

    // Function to set up TextWatcher for OTP EditText fields
    private void setupOtpEditTextListeners() {
        otpDigit1.addTextChangedListener(new OTPTextWatcher(otpDigit1, otpDigit2, null));
        otpDigit2.addTextChangedListener(new OTPTextWatcher(otpDigit2, otpDigit3, otpDigit1));
        otpDigit3.addTextChangedListener(new OTPTextWatcher(otpDigit3, otpDigit4, otpDigit2));
        otpDigit4.addTextChangedListener(new OTPTextWatcher(otpDigit4, otpDigit5, otpDigit3));
        otpDigit5.addTextChangedListener(new OTPTextWatcher(otpDigit5, otpDigit6, otpDigit4));
        otpDigit6.addTextChangedListener(new OTPTextWatcher(otpDigit6, null, otpDigit5));
    }

    // Custom TextWatcher for automatic focus shifting between OTP fields
    private static class OTPTextWatcher implements TextWatcher {
        private final EditText currentEditText;
        private final EditText nextEditText;
        private final EditText previousEditText;

        OTPTextWatcher(EditText currentEditText, EditText nextEditText, EditText previousEditText) {
            this.currentEditText = currentEditText;
            this.nextEditText = nextEditText;
            this.previousEditText = previousEditText;
        }

        @Override
        public void beforeTextChanged(CharSequence charSequence, int start, int count, int after) {
            if (count > 0 && start == 0 && previousEditText != null) {
                previousEditText.requestFocus();
            }
        }

        @Override
        public void onTextChanged(CharSequence charSequence, int start, int before, int count) {
            if (charSequence.length() == 1 && nextEditText != null) {
                nextEditText.requestFocus();
            }
        }

        @Override
        public void afterTextChanged(Editable editable) {
            // No additional logic needed
        }
    }
}
