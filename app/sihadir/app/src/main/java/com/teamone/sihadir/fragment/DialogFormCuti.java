package com.teamone.sihadir.fragment;

import android.app.DatePickerDialog;
import android.os.Bundle;
import android.util.Log;
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
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.CutiRequest;
import com.teamone.sihadir.model.CutiResponse;
import com.teamone.sihadir.model.RetrofitClient;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class DialogFormCuti extends DialogFragment {
    private TextInputEditText edtKeterangan;
    private MaterialButton btnTanggalMulai, btnTanggalSelesai;
    private Button btnSubmit, btnBack;
    private String selectedStartDate = "", selectedEndDate = "";

    private static final String ARG_PEGAWAI_ID = "PEGAWAI_ID";

    private int pegawaiId; // Pegawai ID yang diterima
    private OnLeaveSubmitListener listener;
    private Calendar calendarStart, calendarEnd;


    public static DialogFormCuti newInstance(int pegawaiId) {
        DialogFormCuti fragment = new DialogFormCuti();
        Bundle args = new Bundle();
        args.putInt(ARG_PEGAWAI_ID, pegawaiId);  // Mengirimkan pegawaiId ke bundle
        fragment.setArguments(args);
        return fragment;
    }

    public interface OnLeaveSubmitListener {
        // Update to accept 'int pegawaiId'
        void onLeaveSubmitted(String startDate, String endDate, String keterangan, int pegawaiId);
    }

    public void setOnLeaveSubmitListener(OnLeaveSubmitListener listener) {
        this.listener = listener;
    }

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        if (getArguments() != null) {
            // Retrieve the 'pegawaiId' as an integer
            pegawaiId = getArguments().getInt(ARG_PEGAWAI_ID, -1);  // Default to -1 if not passed
        }
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        return inflater.inflate(R.layout.dialog_cuti, container, false);
    }

    @Override
    public void onViewCreated(View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        calendarStart = Calendar.getInstance();
        calendarEnd = Calendar.getInstance();

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
            dialog.getDatePicker().setMinDate(Calendar.getInstance().getTimeInMillis());
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
        } else if (!selectedEndDate.isEmpty() && calendarEnd.before(calendarStart)) {
            Toast.makeText(requireContext(), "Tanggal selesai tidak boleh sebelum tanggal mulai", Toast.LENGTH_SHORT).show();
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

        // Konversi format tanggal ke YYYY-MM-DD untuk API
        String myFormat = "yyyy-MM-dd"; // Format yang diharapkan oleh API
        SimpleDateFormat dateFormat = new SimpleDateFormat(myFormat, Locale.getDefault());
        String formattedStartDate = dateFormat.format(calendarStart.getTime());
        String formattedEndDate = dateFormat.format(calendarEnd.getTime());

        // Buat objek CutiRequest
        CutiRequest cutiRequest = new CutiRequest(pegawaiId, formattedStartDate, formattedEndDate, keterangan);

        // Panggil API
        ApiService apiService = RetrofitClient.getClient().create(ApiService.class);
        Call<CutiResponse> call = apiService.submitLeaveRequest(cutiRequest);

        call.enqueue(new Callback<CutiResponse>() {
            @Override
            public void onResponse(@NonNull Call<CutiResponse> call, @NonNull Response<CutiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    // Respons berhasil dari API
                    CutiResponse cutiResponse = response.body();
                    Toast.makeText(requireContext(), cutiResponse.getMessage(), Toast.LENGTH_SHORT).show();

                    // Panggil listener untuk melakukan refresh
                    if (getParentFragment() instanceof PerizinanFragment) {
                        ((PerizinanFragment) getParentFragment()).onLeaveSubmitted(
                                formattedStartDate,
                                formattedEndDate,
                                keterangan,
                                pegawaiId
                        );
                    }
                    dismiss(); // Tutup dialog setelah berhasil
                } else {
                    // Log detail jika gagal
                    Log.e("CutiAPI", "Error: " + response.code() + ", Message: " + response.message());
                    try {
                        if (response.errorBody() != null) {
                            Log.e("CutiAPI", "Error Body: " + response.errorBody().string());
                        }
                    } catch (Exception e) {
                        e.printStackTrace();
                    }
                    Toast.makeText(requireContext(), "Gagal mengajukan cuti: " + response.message(), Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<CutiResponse> call, @NonNull Throwable t) {
                // Handle kegagalan jaringan atau lainnya
                Log.e("CutiAPI", "onFailure: " + t.getMessage(), t);
                Toast.makeText(requireContext(), "Terjadi kesalahan, silakan coba lagi", Toast.LENGTH_SHORT).show();
            }
        });
    }

}
