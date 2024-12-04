package com.teamone.sihadir.fragment;

import android.animation.ObjectAnimator;
import android.content.SharedPreferences;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.Display;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.view.WindowManager;
import android.view.animation.AlphaAnimation;
import android.view.animation.Animation;
import android.view.animation.AnimationSet;
import android.view.animation.DecelerateInterpolator;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.fragment.app.Fragment;
import androidx.preference.PreferenceManager;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.textfield.TextInputEditText;
import com.journeyapps.barcodescanner.ScanContract;
import com.journeyapps.barcodescanner.ScanOptions;
import com.teamone.sihadir.R;
import com.teamone.sihadir.StartScan;
import com.teamone.sihadir.model.AbsensiApiResponse;
import com.teamone.sihadir.model.AbsensiRequest;
import com.teamone.sihadir.model.ApiService;
import com.teamone.sihadir.model.AttendanceStatusResponse;
import com.teamone.sihadir.model.RetrofitClient;
import com.teamone.sihadir.model.ScheduleResponse;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.Map;
import java.util.Random;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
public class AbsenFragment extends Fragment {

    private TextView tvShiftSenin, tvJamMasukSenin, tvJamKeluarSenin;
    private TextView tvShiftSelasa, tvJamMasukSelasa, tvJamKeluarSelasa;
    private TextView tvShiftRabu, tvJamMasukRabu, tvJamKeluarRabu;
    private TextView tvShiftKamis, tvJamMasukKamis, tvJamKeluarKamis;
    private TextView tvShiftJumat, tvJamMasukJumat, tvJamKeluarJumat;
    private TextView tvShiftSabtu, tvJamMasukSabtu, tvJamKeluarSabtu;
    private TextView tvShiftMinggu, tvJamMasukMinggu, tvJamKeluarMinggu;
    private Button scanButton1;
    private Button submitButton;
    private TextView TVnamaLengkap;
    private TextView TVemployeeId;
    private TextInputEditText codeInput;
    private ApiService apiService;
    private SharedPreferences sharedPreferences;

    private Handler greetingHandler = new Handler(Looper.getMainLooper());
    private int currentGreetingIndex = 0;

    private void startGreetingAnimation(TextView greetingTextView, String username) {
        greetingHandler.removeCallbacksAndMessages(null); // Clear previous callbacks

        // Array of dynamic messages after initial greeting
        final String[] DYNAMIC_GREETINGS = {
                "Semangat capai target 🚀",
                "Terus produktif ya! 💪🏻",
                "Kerja hebat 👏🏻",
                "Muuacchh 💗"
        };

        // Shuffle the array
        shuffleArray(DYNAMIC_GREETINGS);

        Runnable greetingRunnable = new Runnable() {
            private int currentGreetingIndex = 0;
            private boolean isInitialGreeting = true;

            @Override
            public void run() {
                // Create fade out animation
                AnimationSet animationSet = new AnimationSet(true);
                Animation fadeOut = new AlphaAnimation(1.0f, 0.0f);
                fadeOut.setDuration(500);
                fadeOut.setInterpolator(new DecelerateInterpolator());
                animationSet.addAnimation(fadeOut);

                animationSet.setAnimationListener(new Animation.AnimationListener() {
                    @Override
                    public void onAnimationStart(Animation animation) {}

                    @Override
                    public void onAnimationEnd(Animation animation) {
                        String newGreeting;

                        if (isInitialGreeting) {
                            // After initial time-based greeting, switch to dynamic messages
                            newGreeting = String.format("%s",
                                    DYNAMIC_GREETINGS[currentGreetingIndex]);
                            isInitialGreeting = false;
                        } else {
                            currentGreetingIndex = (currentGreetingIndex + 1) % DYNAMIC_GREETINGS.length;

                            // If we've cycled through all messages, reshuffle and go back to time-based greeting
                            if (currentGreetingIndex == 0) {
                                shuffleArray(DYNAMIC_GREETINGS);
                                String timeGreeting = getTimeBasedGreeting();
                                newGreeting = String.format("%s, %s!", timeGreeting, username);
                                isInitialGreeting = true;
                            } else {
                                newGreeting = String.format("%s",
                                        DYNAMIC_GREETINGS[currentGreetingIndex]);
                            }
                        }

                        greetingTextView.setText(newGreeting);

                        // Fade in animation
                        ObjectAnimator fadeIn = ObjectAnimator.ofFloat(greetingTextView, "alpha", 0f, 1f);
                        fadeIn.setDuration(650);
                        fadeIn.start();
                    }

                    @Override
                    public void onAnimationRepeat(Animation animation) {}
                });

                greetingTextView.startAnimation(animationSet);

                // Schedule next greeting change (3 seconds)
                greetingHandler.postDelayed(this, 4500);
            }
        };

        // Start with time-based greeting
        String timeGreeting = getTimeBasedGreeting();
        greetingTextView.setText(String.format("%s, %s!", timeGreeting, username));

        // Start the recurring animation
        greetingHandler.postDelayed(greetingRunnable, 4500);
    }

    // Helper method to shuffle array
    private void shuffleArray(String[] array) {
        Random random = new Random();
        for (int i = array.length - 1; i > 0; i--) {
            int index = random.nextInt(i + 1);
            // Simple swap
            String temp = array[index];
            array[index] = array[i];
            array[i] = temp;
        }
    }

    private String getTimeBasedGreeting() {
        SimpleDateFormat hourFormat = new SimpleDateFormat("HH", Locale.getDefault());
        int hour = Integer.parseInt(hourFormat.format(new Date()));

        if (hour >= 3 && hour < 11) return "Selamat Pagi";
        if (hour >= 11 && hour < 15) return "Selamat Siang";
        if (hour >= 15 && hour < 18) return "Selamat Sore";
        return "Selamat Malam";
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_absen, container, false);

        // Inisialisasi TextView untuk tanggal
        TextView dateTimeText = view.findViewById(R.id.dateTimeText);

        // Format tanggal
        SimpleDateFormat dateFormat = new SimpleDateFormat("EEEE, dd MMMM yyyy", new Locale("id", "ID"));
        String currentDate = dateFormat.format(new Date());

        // Tampilkan tanggal di TextView
        dateTimeText.setText(currentDate);

        // Inisialisasi Retrofit API Service
        apiService = RetrofitClient.getClient().create(ApiService.class);

        // Inisialisasi SharedPreferences
        sharedPreferences = PreferenceManager.getDefaultSharedPreferences(requireContext());

        // Inisialisasi TextView
        TVnamaLengkap = view.findViewById(R.id.userName);
        TVemployeeId = view.findViewById(R.id.statusChip);

        // Initialize schedule TextViews
        initializeScheduleViews(view);

        // Fetch and populate schedule
        fetchEmployeeSchedule();

        // Ambil data dari SharedPreferences
        String namaLengkap = sharedPreferences.getString("nama_lengkap", "Nama tidak tersedia");
        startGreetingAnimation(TVnamaLengkap, namaLengkap);

        // Inisialisasi TextInputEditText untuk kode absensi
        codeInput = view.findViewById(R.id.codeInput);

        // Menginisialisasi scanButton1 dan submitButton
        scanButton1 = view.findViewById(R.id.scanButton);
        submitButton = view.findViewById(R.id.submitButton);

        // Mengatur refresh rate ke 120Hz jika perangkat mendukung
        setHighRefreshRate();

        // Fetch attendance status
        fetchAttendanceStatus();

        // Menambahkan listener untuk scanButton1
        scanButton1.setOnClickListener(v -> Scanner());

        // Menambahkan listener untuk submitButton
        submitButton.setOnClickListener(v -> submitAbsensi());

        SwipeRefreshLayout swipeRefreshLayout = view.findViewById(R.id.swipeRefreshLayout);
        swipeRefreshLayout.setOnRefreshListener(() -> {
            // Refresh the page content
            fetchEmployeeSchedule();
            fetchAttendanceStatus();

            // Stop the refresh animation
            swipeRefreshLayout.setRefreshing(false);
        });

        return view;

    }

    @Override
    public void onDestroyView() {
        super.onDestroyView();
        // Clean up handler to prevent memory leaks
        if (greetingHandler != null) {
            greetingHandler.removeCallbacksAndMessages(null);
        }
    }

    private void initializeScheduleViews(View view) {
        // Senin
        tvShiftSenin = view.findViewById(R.id.tvShiftSenin);
        tvJamMasukSenin = view.findViewById(R.id.tvJamMasukSenin);
        tvJamKeluarSenin = view.findViewById(R.id.tvJamKeluarSenin);

        // Selasa
        tvShiftSelasa = view.findViewById(R.id.tvShiftSelasa);
        tvJamMasukSelasa = view.findViewById(R.id.tvJamMasukSelasa);
        tvJamKeluarSelasa = view.findViewById(R.id.tvJamKeluarSelasa);

        // Rabu
        tvShiftRabu = view.findViewById(R.id.tvShiftRabu);
        tvJamMasukRabu = view.findViewById(R.id.tvJamMasukRabu);
        tvJamKeluarRabu = view.findViewById(R.id.tvJamKeluarRabu);

        // Kamis
        tvShiftKamis = view.findViewById(R.id.tvShiftKamis);
        tvJamMasukKamis = view.findViewById(R.id.tvJamMasukKamis);
        tvJamKeluarKamis = view.findViewById(R.id.tvJamKeluarKamis);

        // Jumat
        tvShiftJumat = view.findViewById(R.id.tvShiftJumat);
        tvJamMasukJumat = view.findViewById(R.id.tvJamMasukJumat);
        tvJamKeluarJumat = view.findViewById(R.id.tvJamKeluarJumat);

        // Sabtu
        tvShiftSabtu = view.findViewById(R.id.tvShiftSabtu);
        tvJamMasukSabtu = view.findViewById(R.id.tvJamMasukSabtu);
        tvJamKeluarSabtu = view.findViewById(R.id.tvJamKeluarSabtu);

        // Minggu
        tvShiftMinggu = view.findViewById(R.id.tvShiftMinggu);
        tvJamMasukMinggu = view.findViewById(R.id.tvJamMasukMinggu);
        tvJamKeluarMinggu = view.findViewById(R.id.tvJamKeluarMinggu);
    }

    private void fetchEmployeeSchedule() {
        // Get user ID from SharedPreferences
        int userId = sharedPreferences.getInt("user_id", 0);
        if (userId == 0) {
            Toast.makeText(requireContext(), "User ID not found", Toast.LENGTH_SHORT).show();
            return;
        }

        // Call API to fetch schedule
        apiService.getEmployeeSchedule(userId).enqueue(new Callback<ScheduleResponse>() {
            @Override
            public void onResponse(@NonNull Call<ScheduleResponse> call, @NonNull Response<ScheduleResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Map<String, Map<String, String>> schedule = response.body().getSchedule();
                    updateScheduleViews(schedule);
                } else {
                    Toast.makeText(requireContext(), "Failed to fetch schedule", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(@NonNull Call<ScheduleResponse> call, @NonNull Throwable t) {
                Toast.makeText(requireContext(), "Error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void updateScheduleViews(Map<String, Map<String, String>> schedule) {
        // Update Senin
        updateDaySchedule(schedule.get("senin"), tvShiftSenin, tvJamMasukSenin, tvJamKeluarSenin);

        // Update Selasa
        updateDaySchedule(schedule.get("selasa"), tvShiftSelasa, tvJamMasukSelasa, tvJamKeluarSelasa);

        // Update Rabu
        updateDaySchedule(schedule.get("rabu"), tvShiftRabu, tvJamMasukRabu, tvJamKeluarRabu);

        // Update Kamis
        updateDaySchedule(schedule.get("kamis"), tvShiftKamis, tvJamMasukKamis, tvJamKeluarKamis);

        // Update Jumat
        updateDaySchedule(schedule.get("jumat"), tvShiftJumat, tvJamMasukJumat, tvJamKeluarJumat);

        // Update Sabtu
        updateDaySchedule(schedule.get("sabtu"), tvShiftSabtu, tvJamMasukSabtu, tvJamKeluarSabtu);

        // Update Minggu
        updateDaySchedule(schedule.get("minggu"), tvShiftMinggu, tvJamMasukMinggu, tvJamKeluarMinggu);
    }

    private void updateDaySchedule(Map<String, String> daySchedule, TextView shiftTV, TextView jamMasukTV, TextView jamKeluarTV) {
        if (daySchedule != null) {
            shiftTV.setText(daySchedule.get("shift_name"));
            jamMasukTV.setText(daySchedule.get("jam_masuk"));
            jamKeluarTV.setText(daySchedule.get("jam_keluar"));
        }
    }

    private void fetchAttendanceStatus() {
        int userId = sharedPreferences.getInt("user_id", 0);
        if (userId == 0) {
            Toast.makeText(requireContext(), "User ID not found", Toast.LENGTH_SHORT).show();
            return;
        }

        apiService.getAttendanceStatus(userId).enqueue(new Callback<AttendanceStatusResponse>() {
            @Override
            public void onResponse(@NonNull Call<AttendanceStatusResponse> call, @NonNull Response<AttendanceStatusResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    AttendanceStatusResponse.Attendance attendance = response.body().getAttendance();
                    if (attendance != null) {
                        updateAttendanceStatusView(attendance.getStatusKehadiran());
                    }
                } else {
                    TVemployeeId.setText("Status tidak tersedia");
                }
            }

            @Override
            public void onFailure(@NonNull Call<AttendanceStatusResponse> call, @NonNull Throwable t) {
                TVemployeeId.setText("Error: " + t.getMessage());
            }
        });
    }

    private void updateAttendanceStatusView(String status) {
        // Map status to user-friendly text and potentially different colors
        switch (status.toLowerCase()) {
            case "hadir":
                TVemployeeId.setText("Anda sudah absensi hari ini");
                break;
            case "izin":
                TVemployeeId.setText("Anda hari ini izin");
                break;
            case "cuti":
                TVemployeeId.setText("Anda hari ini cuti");
                break;
            case "sakit":
                TVemployeeId.setText("Anda sakit, semoga lekas sembuh");
                break;
            case "alpha":
                TVemployeeId.setText("Anda belum absensi hari ini");
                break;
            case "pulang_dahulu":
                TVemployeeId.setText("Anda pulang dahulu hari ini");
                break;
            case "terlambat":
                TVemployeeId.setText("Anda terlambat hari ini");
                break;
            default:
                TVemployeeId.setText(status);
        }
    }

    private void submitAbsensiWithScannedCode(String scannedCode) {
        codeInput.setText(scannedCode);
        submitAbsensi();
        fetchAttendanceStatus();
    }

    private void Scanner() {
        // Membuat opsi pemindaian QR
        ScanOptions options = new ScanOptions();
        options.setPrompt("Volume up to flash on");
        options.setBeepEnabled(true);
        options.setOrientationLocked(true);
        options.setCaptureActivity(StartScan.class);
        launcher.launch(options); // Meluncurkan pemindaian
    }

    private final ActivityResultLauncher<ScanOptions> launcher = registerForActivityResult(new ScanContract(), result -> {
        if (result.getContents() != null) {
            String scannedCode = result.getContents();

            // Validasi panjang kode
            if (scannedCode.length() > 6) {
                // Jika lebih dari 6 digit, tampilkan pesan error
                showErrorDialog("Kode Tidak Valid", "Kode absensi harus 6 digit atau kurang");
            } else {
                // Langsung submit kode yang di-scan
                submitAbsensiWithScannedCode(scannedCode);
            }
        }
    });

    private void submitAbsensi() {
        String absensiCode = codeInput.getText().toString().trim();
        if (absensiCode.isEmpty()) {
            showErrorDialog("Kode Kosong", "Silakan scan atau masukkan kode absensi");
            return;
        }

        int userId = sharedPreferences.getInt("user_id", 0);
        if (userId == 0) {
            showErrorDialog("Error", "User ID tidak ditemukan. Pastikan Anda telah login.");
            return;
        }

        // Check early leave status before submitting
        checkEarlyLeaveStatus(userId, absensiCode);
    }

    private void checkEarlyLeaveStatus(int userId, String absensiCode) {
        // Prepare the request body
        AbsensiRequest request = new AbsensiRequest(
                String.valueOf(userId),
                absensiCode,
                false // Initial confirmation set to false
        );

        apiService.submitAbsensi(request).enqueue(new Callback<AbsensiApiResponse>() {
            @Override
            public void onResponse(@NonNull Call<AbsensiApiResponse> call, @NonNull Response<AbsensiApiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    AbsensiApiResponse apiResponse = response.body();

                    // Check if confirmation is needed for early leave
                    if ("confirm_needed".equalsIgnoreCase(apiResponse.getStatus())) {
                        showEarlyLeaveConfirmationDialog(
                                () -> sendFinalAbsensiRequest(userId, absensiCode, true),
                                () -> showErrorDialog("Konfirmasi Diperlukan", "Konfirmasi pulang lebih awal tidak dilakukan.")
                        );
                    } else if ("success".equalsIgnoreCase(apiResponse.getStatus())) {
                        // Absensi successful without confirmation needed
                        Toast.makeText(requireContext(), apiResponse.getMessage(), Toast.LENGTH_SHORT).show();
                        codeInput.setText(""); // Reset input
                        fetchAttendanceStatus();
                    } else {
                        // Other error cases
                        showErrorDialog("Gagal", apiResponse.getMessage());
                    }
                } else {
                    showErrorDialog("Gagal", "Tidak dapat mengirim absensi. Silakan coba lagi.");
                }
            }

            @Override
            public void onFailure(@NonNull Call<AbsensiApiResponse> call, @NonNull Throwable t) {
                showErrorDialog("Error", "Terjadi kesalahan: " + t.getMessage());
            }
        });
    }

    private void sendFinalAbsensiRequest(int userId, String absensiCode, boolean confirmEarlyLeave) {
        AbsensiRequest finalRequest = new AbsensiRequest(
                String.valueOf(userId),
                absensiCode,
                confirmEarlyLeave
        );

        apiService.submitAbsensi(finalRequest).enqueue(new Callback<AbsensiApiResponse>() {
            @Override
            public void onResponse(@NonNull Call<AbsensiApiResponse> call, @NonNull Response<AbsensiApiResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    AbsensiApiResponse apiResponse = response.body();

                    if ("success".equalsIgnoreCase(apiResponse.getStatus())) {
                        Toast.makeText(requireContext(), apiResponse.getMessage(), Toast.LENGTH_SHORT).show();
                        codeInput.setText(""); // Reset input
                        fetchAttendanceStatus();
                    } else {
                        showErrorDialog("Gagal", apiResponse.getMessage());
                    }
                } else {
                    showErrorDialog("Gagal", "Tidak dapat mengirim absensi. Silakan coba lagi.");
                }
            }

            @Override
            public void onFailure(@NonNull Call<AbsensiApiResponse> call, @NonNull Throwable t) {
                showErrorDialog("Error", "Terjadi kesalahan: " + t.getMessage());
            }
        });
    }

    private void showEarlyLeaveConfirmationDialog(Runnable onConfirm, Runnable onCancel) {
        AlertDialog.Builder builder = new AlertDialog.Builder(requireContext());
        builder.setTitle("Konfirmasi Pulang Lebih Awal");
        builder.setMessage("Anda akan pulang sebelum jam shift berakhir. Apakah Anda yakin?");
        builder.setPositiveButton("Ya", (dialog, which) -> {
            dialog.dismiss();
            onConfirm.run();
        });
        builder.setNegativeButton("Tidak", (dialog, which) -> {
            dialog.dismiss();
            onCancel.run();
        });
        builder.setCancelable(false);
        builder.show();
    }


    private void setHighRefreshRate() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R && getActivity() != null) {
            WindowManager.LayoutParams layoutParams = getActivity().getWindow().getAttributes();
            layoutParams.preferredDisplayModeId = findPreferredDisplayMode(120f);
            getActivity().getWindow().setAttributes(layoutParams);
        }
    }

    private int findPreferredDisplayMode(float targetRefreshRate) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R && getActivity() != null) {
            WindowManager windowManager = getActivity().getSystemService(WindowManager.class);
            if (windowManager != null) {
                Display display = windowManager.getDefaultDisplay();
                if (display != null) {
                    Display.Mode[] supportedModes = display.getSupportedModes();
                    for (Display.Mode mode : supportedModes) {
                        if (Math.abs(mode.getRefreshRate() - targetRefreshRate) < 0.1f) {
                            return mode.getModeId();
                        }
                    }
                }
            }
        }
        return 0;
    }

    private void showErrorDialog(String title, String message) {
        AlertDialog.Builder builder = new AlertDialog.Builder(requireContext());
        builder.setTitle(title);
        builder.setMessage(message);
        builder.setPositiveButton("OK", (dialog, which) -> dialog.dismiss());
        builder.show();
    }

    private void showConfirmationDialog(String title, String message) {
        AlertDialog.Builder builder = new AlertDialog.Builder(requireContext());
        builder.setTitle(title);
        builder.setMessage(message);
        builder.setPositiveButton("OK", (dialog, which) -> dialog.dismiss());
        builder.show();
    }
}