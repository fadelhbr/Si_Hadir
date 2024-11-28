package com.teamone.sihadir.fragment;

import android.app.DatePickerDialog;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.AutoCompleteTextView;
import android.widget.Button;
import android.widget.DatePicker;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.DialogFragment;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import com.teamone.sihadir.R;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Locale;

public class PermissionFormDialog extends DialogFragment {
    private AutoCompleteTextView spinnerJenisIzin;
    private TextInputEditText edtKeterangan;
    private MaterialButton btnTanggal;
    private Button btnSubmit, btnBack;
    private String selectedDate = "";
    private OnPermissionSubmitListener listener;
    private Calendar calendar;

    public interface OnPermissionSubmitListener {
        void onPermissionSubmitted(String jenisIzin, String keterangan);

        void onPermissionSubmitted(String jenisIzin, String tanggal, String keterangan);
    }

    public void setOnPermissionSubmitListener(OnPermissionSubmitListener listener) {
        this.listener = listener;
    }

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setStyle(DialogFragment.STYLE_NO_TITLE, R.style.DialogTheme);
        calendar = Calendar.getInstance();
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.formizin, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        // Initialize views
        spinnerJenisIzin = view.findViewById(R.id.spinnerJenisIzin);
        edtKeterangan = view.findViewById(R.id.edtKeterangan);
        btnTanggal = view.findViewById(R.id.btnTanggal);
        btnSubmit = view.findViewById(R.id.btnSubmit);
        btnBack = view.findViewById(R.id.btnBack);

        // Setup date picker
        setupDatePicker();

        // Setup dropdown menu for jenis izin
        String[] jenisIzinItems = new String[]{
                "Sakit",
                "Keperluan Keluarga",
                "Keperluan Pribadi",
                "Lainnya"
        };

        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                requireContext(),
                android.R.layout.simple_dropdown_item_1line,
                jenisIzinItems
        );
        spinnerJenisIzin.setAdapter(adapter);

        btnBack.setOnClickListener(v -> dismiss());
        btnSubmit.setOnClickListener(v -> {
            if (validateForm()) {
                submitForm();
            }
        });
    }

    private void setupDatePicker() {
        DatePickerDialog.OnDateSetListener date = (view, year, month, day) -> {
            calendar.set(Calendar.YEAR, year);
            calendar.set(Calendar.MONTH, month);
            calendar.set(Calendar.DAY_OF_MONTH, day);
            updateLabel();
        };

        btnTanggal.setOnClickListener(v -> {
            DatePickerDialog dialog = new DatePickerDialog(requireContext(), date,
                    calendar.get(Calendar.YEAR),
                    calendar.get(Calendar.MONTH),
                    calendar.get(Calendar.DAY_OF_MONTH));
            dialog.show();
        });
    }

    private void updateLabel() {
        String myFormat = "dd/MM/yyyy";
        SimpleDateFormat dateFormat = new SimpleDateFormat(myFormat, new Locale("id", "ID"));
        selectedDate = dateFormat.format(calendar.getTime());
        btnTanggal.setText("Tanggal: " + selectedDate);
    }

    private boolean validateForm() {
        boolean isValid = true;
        String jenisIzin = spinnerJenisIzin.getText().toString();
        String keterangan = edtKeterangan.getText().toString().trim();

        if (selectedDate.isEmpty()) {
            Toast.makeText(requireContext(), "Silakan pilih tanggal", Toast.LENGTH_SHORT).show();
            isValid = false;
        }

        if (jenisIzin.isEmpty()) {
            spinnerJenisIzin.setError("Silakan pilih jenis izin");
            isValid = false;
        }

        if (keterangan.isEmpty()) {
            edtKeterangan.setError("Keterangan tidak boleh kosong");
            isValid = false;
        }

        return isValid;
    }

    private void submitForm() {
        String jenisIzin = spinnerJenisIzin.getText().toString();
        String keterangan = edtKeterangan.getText().toString().trim();

        if (listener != null) {
            listener.onPermissionSubmitted(jenisIzin, selectedDate, keterangan);
        }

        Toast.makeText(requireContext(), "Permohonan izin berhasil diajukan", Toast.LENGTH_SHORT).show();
        dismiss();
    }
}