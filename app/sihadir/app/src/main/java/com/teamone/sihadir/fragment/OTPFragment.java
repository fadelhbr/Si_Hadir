package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.google.android.material.button.MaterialButton;
import com.teamone.sihadir.R;

public class OTPFragment extends Fragment {

    private EditText otpDigit1, otpDigit2, otpDigit3, otpDigit4, otpDigit5, otpDigit6;
    private MaterialButton btnVerify;
    private TextView resendOTP, emailText;
    private String email;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.otp_activity, container, false);

        // Inisialisasi elemen
        otpDigit1 = view.findViewById(R.id.otp_digit1);
        otpDigit2 = view.findViewById(R.id.otp_digit2);
        otpDigit3 = view.findViewById(R.id.otp_digit3);
        otpDigit4 = view.findViewById(R.id.otp_digit4);
        otpDigit5 = view.findViewById(R.id.otp_digit5);
        otpDigit6 = view.findViewById(R.id.otp_digit6);
        btnVerify = view.findViewById(R.id.BtnVerifikasi);
        resendOTP = view.findViewById(R.id.ResendOTP);
        emailText = view.findViewById(R.id.email_text);

        // Ambil email dari argument
        if (getArguments() != null) {
            email = getArguments().getString("email");
            emailText.setText(email);
        }

        // Verifikasi OTP
        btnVerify.setOnClickListener(v -> {
            String otp = otpDigit1.getText().toString() +
                    otpDigit2.getText().toString() +
                    otpDigit3.getText().toString() +
                    otpDigit4.getText().toString() +
                    otpDigit5.getText().toString() +
                    otpDigit6.getText().toString();

            if (otp.length() < 6) {
                Toast.makeText(getActivity(), "Masukkan kode OTP yang lengkap", Toast.LENGTH_SHORT).show();
            } else {
                verifyOTP(email, otp);
            }
        });

        // Kirim ulang OTP
        resendOTP.setOnClickListener(v -> sendOTP(email));

        return view;
    }

    private void verifyOTP(String email, String otp) {
        // Implementasikan logika HTTP untuk memverifikasi OTP
        Toast.makeText(getActivity(), "OTP berhasil diverifikasi!", Toast.LENGTH_SHORT).show();

        // Navigasi ke ResetPasswordFragment
        requireActivity().getSupportFragmentManager()
                .beginTransaction()
                .replace(R.id.fragment_container, new ResetPasswordFragment())
                .addToBackStack(null)
                .commit();
    }

    private void sendOTP(String email) {
        // Implementasikan logika HTTP untuk mengirim ulang OTP
        Toast.makeText(getActivity(), "Kode OTP telah dikirim ulang ke " + email, Toast.LENGTH_SHORT).show();
    }
}
