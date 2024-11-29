package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;

import androidx.fragment.app.Fragment;
import androidx.appcompat.app.AppCompatActivity;

import com.teamone.sihadir.LoginActivity;
import com.teamone.sihadir.R;

public class LogoutFragment extends Fragment {

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_logout, container, false);

        Button btnLogout = view.findViewById(R.id.btn_logout);

        btnLogout.setOnClickListener(v -> {
            // Gunakan metode logout statis dari LoginActivity
            if (getActivity() != null) {
                LoginActivity.logout((AppCompatActivity) getActivity());
            }
        });

        return view;
    }
}