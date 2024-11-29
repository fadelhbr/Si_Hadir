package com.teamone.sihadir.fragment;

import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import com.google.android.material.textfield.TextInputLayout;
import com.teamone.sihadir.LoginActivity;
import com.teamone.sihadir.R;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

public class ResetPasswordFragment extends Fragment {

    private TextInputEditText etNewPassword, etConfirmPassword;
    private MaterialButton btnResetPassword;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.activity_reset_password, container, false);

        // Inisialisasi elemen
        etNewPassword = view.findViewById(R.id.et_new_password);
        etConfirmPassword = view.findViewById(R.id.et_confirm_password);
        btnResetPassword = view.findViewById(R.id.btn_reset_password);

        // Tombol Reset Password
        btnResetPassword.setOnClickListener(v -> {
            String newPassword = etNewPassword.getText().toString().trim();
            String confirmPassword = etConfirmPassword.getText().toString().trim();

            if (newPassword.isEmpty() || confirmPassword.isEmpty()) {
                Toast.makeText(getActivity(), "Password tidak boleh kosong", Toast.LENGTH_SHORT).show();
            } else if (!newPassword.equals(confirmPassword)) {
                Toast.makeText(getActivity(), "Password tidak cocok", Toast.LENGTH_SHORT).show();
            } else {
                resetPassword(newPassword);
            }
        });

        return view;
    }

    private void resetPassword(String newPassword) {
        // Implementasikan logika HTTP untuk mengirim password baru ke server
        Toast.makeText(getActivity(), "Password berhasil diubah!", Toast.LENGTH_SHORT).show();
        // Navigasi kembali
        requireActivity().getSupportFragmentManager().popBackStack();
    }
}
