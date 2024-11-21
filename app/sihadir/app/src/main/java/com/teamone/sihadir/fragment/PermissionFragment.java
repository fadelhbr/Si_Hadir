package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import androidx.fragment.app.Fragment;

import com.teamone.sihadir.R;

public class PermissionFragment extends Fragment {

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_perizinan, container, false); // Fungsi dikosongkan
    }

    @Override
    public void onViewCreated(View view, Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
    }

    private void updateNotifikasiKeServer(boolean isActive) {
        // Fungsi dikosongkan
    }

    private void updateTema(boolean isNightMode) {
        // Fungsi dikosongkan
    }

    private void logout() {
        // Fungsi dikosongkan
    }
}
