package com.teamone.sihadir.fragment;

import android.app.DatePickerDialog;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
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

public class CutiFormDialog extends DialogFragment {
    private TextInputEditText edtKeterangan;
    private MaterialButton btnTanggalMulai, btnTanggalSelesai;
    private Button btnSubmit, btnBack;
    private String selectedStartDate = "", selectedEndDate = "";
    private OnLeaveSubmitListener listener;
    private Calendar calendarStart, calendarEnd;

    public interface OnLeaveSubmitListener {
        void onLeaveSubmitted(String startDate, String endDate, String keterangan);
    }

    public void setOnLeaveSubmitListener(OnLeaveSubmitListener listener) {
        this.listener = listener;
    }

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setStyle(DialogFragment.STYLE_NO_TITLE, R.style.DialogTheme);
        calendarStart = Calendar.getInstance();
        calendarEnd = Calendar.getInstance();
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.formcuti, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        edtKeterangan = view.findViewById(R.id.edtKeterangan);
        btnTanggalMulai = view.findViewById(R.id.btnTanggalMulai);
        btnTanggalSelesai = view.findViewById(R.id.btnTanggalSelesai);
        btnSubmit = view.findViewById(R.id.btnSubmit);
        btnBack = view.findViewById(R.id.btnBack);

        setupDatePickers();

        btnBack.setOnClickListener(v -> dismiss());
        btnSubmit.setOnClickListener(v -> {
            if (validateForm()) {
                submitForm();
            }
        });
    }

    private void setupDatePickers() {
        DatePickerDialog.OnDateSetListener startDate = (view, year, month, day) -> {
            calendarStart.set(Calendar.YEAR, year);
            calendarStart.set(Calendar.MONTH, month);
            calendarStart.set(Calendar.DAY_OF_MONTH, day);
            updateStartDateLabel();
        };

        DatePickerDialog.OnDateSetListener endDate = (view, year, month, day) -> {
            calendarEnd.set(Calendar.YEAR, year);
            calendarEnd.set(Calendar.MONTH, month);
            calendarEnd.set(Calendar.DAY_OF_MONTH, day);
            updateEndDateLabel();
        };

        btnTanggalMulai.setOnClickListener(v -> {
            DatePickerDialog dialog = new DatePickerDialog(requireContext(), startDate,
                    calendarStart.get(Calendar.YEAR),
                    calendarStart.get(Calendar.MONTH),
                    calendarStart.get(Calendar.DAY_OF_MONTH));
            dialog.show();
        });

        btnTanggalSelesai.setOnClickListener(v -> {
            DatePickerDialog dialog = new DatePickerDialog(requireContext(), endDate,
                    calendarEnd.get(Calendar.YEAR),
                    calendarEnd.get(Calendar.MONTH),
                    calendarEnd.get(Calendar.DAY_OF_MONTH));
            dialog.getDatePicker().setMinDate(calendarStart.getTimeInMillis()); // Set minimum date to start date
            dialog.show();
        });
    }

    private void updateStartDateLabel() {
        String myFormat = "dd/MM/yyyy";
        SimpleDateFormat dateFormat = new SimpleDateFormat(myFormat, new Locale("id", "ID"));
        selectedStartDate = dateFormat.format(calendarStart.getTime());
        btnTanggalMulai.setText("Mulai: " + selectedStartDate);
    }

    private void updateEndDateLabel() {
        String myFormat = "dd/MM/yyyy";
        SimpleDateFormat dateFormat = new SimpleDateFormat(myFormat, new Locale("id", "ID"));
        selectedEndDate = dateFormat.format(calendarEnd.getTime());
        btnTanggalSelesai.setText("Selesai: " + selectedEndDate);
    }

    private boolean validateForm() {
        boolean isValid = true;
        String keterangan = edtKeterangan.getText().toString().trim();

        if (selectedStartDate.isEmpty()) {
            Toast.makeText(requireContext(), "Silakan pilih tanggal mulai", Toast.LENGTH_SHORT).show();
            isValid = false;
        }

        if (selectedEndDate.isEmpty()) {
            Toast.makeText(requireContext(), "Silakan pilih tanggal selesai", Toast.LENGTH_SHORT).show();
            isValid = false;
        }

        if (keterangan.isEmpty()) {
            edtKeterangan.setError("Keterangan tidak boleh kosong");
            isValid = false;
        }

        return isValid;
    }

    private void submitForm() {
        String keterangan = edtKeterangan.getText().toString().trim();

        if (listener != null) {
            listener.onLeaveSubmitted(selectedStartDate, selectedEndDate, keterangan);
        }

        Toast.makeText(requireContext(), "Permohonan cuti berhasil diajukan", Toast.LENGTH_SHORT).show();
        dismiss();
    }
}