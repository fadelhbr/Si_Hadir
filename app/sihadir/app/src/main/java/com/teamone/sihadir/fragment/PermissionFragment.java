package com.teamone.sihadir.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import com.google.android.material.button.MaterialButton;
import com.teamone.sihadir.R;

public class PermissionFragment extends Fragment implements
        PermissionFormDialog.OnPermissionSubmitListener,
        CutiFormDialog.OnLeaveSubmitListener {

    private MaterialButton btnAjukanIzin;
    private MaterialButton btnAjukanCuti;

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_perizinan, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        btnAjukanIzin = view.findViewById(R.id.btnAjukanIzin);
        btnAjukanCuti = view.findViewById(R.id.btnAjukanCuti);

        btnAjukanIzin.setOnClickListener(v -> showPermissionForm());
        btnAjukanCuti.setOnClickListener(v -> showLeaveForm());
    }

    private void showPermissionForm() {
        PermissionFormDialog dialog = new PermissionFormDialog();
        dialog.setOnPermissionSubmitListener(this);
        dialog.show(getChildFragmentManager(), "PermissionFormDialog");
    }

    private void showLeaveForm() {
        CutiFormDialog dialog = new CutiFormDialog();
        dialog.setOnLeaveSubmitListener(this);
        dialog.show(getChildFragmentManager(), "CutiFormDialog");
    }

    // Implementation for the two-parameter version
    @Override
    public void onPermissionSubmitted(String jenisIzin, String keterangan) {
        // Handle submitted permission data here (two-parameter version)
        refreshPermissionHistory();
    }

    // Implementation for the three-parameter version
    @Override
    public void onPermissionSubmitted(String jenisIzin, String tanggal, String keterangan) {
        // Handle submitted permission data here (three-parameter version)
        refreshPermissionHistory();
    }

    @Override
    public void onLeaveSubmitted(String startDate, String endDate, String keterangan) {
        // Handle submitted leave data here
        refreshPermissionHistory();
    }

    private void refreshPermissionHistory() {
        // Implement your refresh logic here
        // This method should fetch both permission and leave history
        refreshPermissionData();
        refreshLeaveData();
    }

    private void refreshPermissionData() {
        // Implement API call to fetch permission data
    }

    private void refreshLeaveData() {
        // Implement API call to fetch leave data
    }
}