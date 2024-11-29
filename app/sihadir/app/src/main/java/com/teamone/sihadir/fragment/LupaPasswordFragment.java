package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.util.Patterns;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import com.teamone.sihadir.R;

public class LupaPasswordFragment extends Fragment {

    private TextInputEditText emailInput;
    private MaterialButton btnSend;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.lupa_password, container, false);

        // Inisialisasi elemen
        emailInput = view.findViewById(R.id.Email_input);
        btnSend = view.findViewById(R.id.btnSend);
        // Tombol Kirim OTP
        btnSend.setOnClickListener(v -> {
            String email = emailInput.getText().toString().trim();

            if (email.isEmpty()) {
                Toast.makeText(getActivity(), "Email tidak boleh kosong", Toast.LENGTH_SHORT).show();
            } else if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
                Toast.makeText(getActivity(), "Email tidak valid", Toast.LENGTH_SHORT).show();
            } else {
                sendOTP(email);
            }
        });

        return view;
    }

    private void sendOTP(String email) {
        // Implementasikan logika HTTP untuk mengirim OTP
        Toast.makeText(getActivity(), "OTP telah dikirim ke " + email, Toast.LENGTH_SHORT).show();

        // Navigasi ke fragment OTP
        Bundle bundle = new Bundle();
        bundle.putString("email", email);
        OTPFragment otpFragment = new OTPFragment();
        otpFragment.setArguments(bundle);

        requireActivity().getSupportFragmentManager()
                .beginTransaction()
                .replace(R.id.fragment_container, otpFragment)
                .addToBackStack(null)
                .commit();
    }
}
