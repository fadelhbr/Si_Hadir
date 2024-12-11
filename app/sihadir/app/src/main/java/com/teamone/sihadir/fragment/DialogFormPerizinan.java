package com.teamone.sihadir.fragment;

import android.app.DatePickerDialog;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.AutoCompleteTextView;
import android.widget.Button;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.DialogFragment;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import com.teamone.sihadir.R;
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.IzinRequest;
import com.teamone.sihadir.model.IzinResponse;
import com.teamone.sihadir.model.RetrofitClient;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class DialogFormPerizinan extends DialogFragment {
    private AutoCompleteTextView spinnerJenisIzin;
    private TextInputEditText edtKeterangan;
    private MaterialButton btnTanggal;
    private Button btnSubmit, btnBack;
    private String selectedDate = "";
    private OnPermissionSubmitListener listener;
    private Calendar calendar;
    private int pegawaiId; // Menyimpan pegawai_id sebagai String

    public interface OnPermissionSubmitListener {
        void onPermissionSubmitted(String jenisIzin, String tanggal, String keterangan);
    }

    public void setOnPermissionSubmitListener(OnPermissionSubmitListener listener) {
        this.listener = listener;
    }

    public static DialogFormPerizinan newInstance(int pegawaiId) { // Ganti int ke String
        DialogFormPerizinan dialog = new DialogFormPerizinan();
        Bundle args = new Bundle();
        args.putInt("pegawai_id", pegawaiId); // Simpan pegawaiId sebagai String
        dialog.setArguments(args);
        return dialog;
    }

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setStyle(DialogFragment.STYLE_NO_TITLE, R.style.DialogTheme);
        calendar = Calendar.getInstance();
        if (getArguments() != null) {
            pegawaiId = getArguments().getInt("pegawai_id", -1); // Ambil pegawaiId sebagai String
        }
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.dialog_izin, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        spinnerJenisIzin = view.findViewById(R.id.spinnerJenisIzin);
        edtKeterangan = view.findViewById(R.id.edtKeterangan);
        btnTanggal = view.findViewById(R.id.btnTanggal);
        btnSubmit = view.findViewById(R.id.btnSubmit);
        btnBack = view.findViewById(R.id.btnBack);

        setupDatePicker();

        String[] jenisIzinItems = new String[]{"Sakit", "Keperluan Pribadi", "Dinas Luar"};
        ArrayAdapter<String> adapter = new ArrayAdapter<>(requireContext(), android.R.layout.simple_dropdown_item_1line, jenisIzinItems);
        spinnerJenisIzin.setAdapter(adapter);

        btnBack.setOnClickListener(v -> dismiss());
        btnSubmit.setOnClickListener(v -> {
            if (validateForm()) {
                submitForm();
            }
        });
    }

    private void setupDatePicker() {
        // Menentukan tanggal yang dipilih pada dialog
        DatePickerDialog.OnDateSetListener date = (view, year, month, dayOfMonth) -> {
            // Mengupdate tanggal yang dipilih
            calendar.set(Calendar.YEAR, year);
            calendar.set(Calendar.MONTH, month);
            calendar.set(Calendar.DAY_OF_MONTH, dayOfMonth);

            // Update label dengan tanggal yang dipilih
            updateLabel();
        };

        // Listener untuk tombol pilih tanggal
        btnTanggal.setOnClickListener(v -> {
            DatePickerDialog dialog = new DatePickerDialog(
                    requireContext(),
                    date,
                    calendar.get(Calendar.YEAR),
                    calendar.get(Calendar.MONTH),
                    calendar.get(Calendar.DAY_OF_MONTH)
            );

            // Mengatur tanggal minimum (tidak bisa memilih tanggal sebelum hari ini)
            dialog.getDatePicker().setMinDate(System.currentTimeMillis());
            dialog.show();
        });
    }

    private void updateLabel() {
        // Format tanggal yang dipilih
        String myFormat = "yyyy-MM-dd"; // Sesuaikan format sesuai kebutuhan
        SimpleDateFormat sdf = new SimpleDateFormat(myFormat, Locale.US);

        // Set tanggal pada button dan simpan di selectedDate
        String formattedDate = sdf.format(calendar.getTime());
        btnTanggal.setText(formattedDate);
        selectedDate = formattedDate; // Simpan tanggal yang dipilih
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
        String keterangan = edtKeterangan.getText().toString().trim();
        String jenisIzin = spinnerJenisIzin.getText().toString();
        String tanggal = selectedDate; // Mengambil tanggal yang dipilih
        String myFormat = "yyyy/MM/dd";

        // Menyesuaikan dengan format ENUM di database
        if (jenisIzin.equals("Sakit")) {
            jenisIzin = "sakit";  // Sesuaikan dengan format ENUM di database
        } else if (jenisIzin.equals("Keperluan Pribadi")) {
            jenisIzin = "keperluan_pribadi";  // Sesuaikan dengan format ENUM di database
        } else if (jenisIzin.equals("Dinas Luar")) {
            jenisIzin = "dinas_luar";  // Sesuaikan dengan format ENUM di database
        }

        // Cek apakah semua data telah diisi
        if (tanggal.isEmpty() || jenisIzin.isEmpty() || keterangan.isEmpty()) {
            Toast.makeText(requireContext(), "Harap lengkapi semua data", Toast.LENGTH_SHORT).show();
            return;
        }

        // Panggil API untuk mengajukan izin
        ApiService apiService = RetrofitClient.getClient().create(ApiService.class);
        Call<IzinResponse> call = apiService.submitIzinRequest(new IzinRequest(pegawaiId, tanggal, jenisIzin, keterangan));

        String finalJenisIzin = jenisIzin;
        call.enqueue(new Callback<IzinResponse>() {
            @Override
            public void onResponse(@NonNull Call<IzinResponse> call, @NonNull Response<IzinResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    IzinResponse izinResponse = response.body();
                    if ("error".equals(izinResponse.getStatus()) && izinResponse.getMessage().contains("Tanggal tersebut")) {
                        // Tanggal sudah digunakan, tampilkan pesan error dari API
                        Toast.makeText(requireContext(), izinResponse.getMessage(), Toast.LENGTH_SHORT).show();
                    } else {
                        // Pengajuan izin berhasil
                        Toast.makeText(requireContext(), izinResponse.getMessage(), Toast.LENGTH_SHORT).show();

                        // Panggil listener untuk melakukan refresh
                        if (getParentFragment() instanceof PerizinanFragment) {
                            ((PerizinanFragment) getParentFragment()).onPermissionSubmitted(
                                    finalJenisIzin,
                                    tanggal,
                                    keterangan
                            );
                        }
                        dismiss(); // Tutup dialog setelah berhasil
                    }
                } else {
                    Toast.makeText(requireContext(), "Gagal mengajukan izin, coba lagi.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<IzinResponse> call, @NonNull Throwable t) {
                // Handle kegagalan jaringan atau lainnya
                Log.e("IzinAPI", "onFailure: " + t.getMessage(), t);
                Toast.makeText(requireContext(), "Terjadi kesalahan, silakan coba lagi", Toast.LENGTH_SHORT).show();
            }
        });
    }
}