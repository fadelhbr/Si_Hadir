package com.teamone.sihadir;

import android.animation.AnimatorSet;
import android.animation.ObjectAnimator;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.os.Handler;
import android.view.View;
import android.view.animation.AccelerateDecelerateInterpolator;
import android.view.animation.AnticipateOvershootInterpolator;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.preference.PreferenceManager;
import com.google.gson.Gson;
import okhttp3.*;
import org.json.JSONObject;
import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

public class LoginActivity extends AppCompatActivity {

    private EditText usernameEditText, passwordEditText;
    private Button btnLogin;
    private TextView btnForgotPassword;
    private static final String API_URL = "http://10.0.2.2/Si_Hadir/web/sihadir/app/api/api_login.php";
    private static final MediaType JSON = MediaType.parse("application/json; charset=utf-8");

    // SharedPreferences keys
    private static final String PREF_IS_LOGGED_IN = "is_logged_in";
    private static final String PREF_USER_ID = "user_id";
    private static final String PREF_USERNAME = "username";
    private static final String PREF_USER_ROLE = "user_role";
    private static final String PREF_NAMA_LENGKAP = "nama_lengkap";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Periksa apakah sudah login
        if (isLoggedIn()) {
            navigateToMainActivity();
            return;
        }

        setContentView(R.layout.activity_login);

        // Inisialisasi komponen
        usernameEditText = findViewById(R.id.txtUsername);
        passwordEditText = findViewById(R.id.txtPassword);
        btnLogin = findViewById(R.id.btnLogin);
        btnForgotPassword = findViewById(R.id.btnForgotPassword);

        // Tambahkan animasi awal
        initializeEntryAnimations();

        btnLogin.setOnClickListener(v -> {
            // Tambahkan animasi ketika login diklik
            animateLoginClick();
            login();
        });

        btnForgotPassword.setOnClickListener(v -> {
            // Aksi ketika lupa password diklik
            Toast.makeText(LoginActivity.this, "Lupa password diklik", Toast.LENGTH_SHORT).show();
            // Anda bisa menambahkan logika untuk menangani fitur lupa password di sini.
        });
    }

    private boolean isLoggedIn() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);
        return preferences.getBoolean(PREF_IS_LOGGED_IN, false);
    }

    private void saveLoginInfo(int userId, String username, String userRole) {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);
        SharedPreferences.Editor editor = preferences.edit();

        // Capitalize username for display
        String displayName = username.substring(0, 1).toUpperCase() + username.substring(1).toLowerCase();

        editor.putBoolean(PREF_IS_LOGGED_IN, true);
        editor.putInt(PREF_USER_ID, userId);
        editor.putString(PREF_USERNAME, username);
        editor.putString(PREF_USER_ROLE, userRole);
        editor.putString(PREF_NAMA_LENGKAP, displayName);
        editor.apply();
    }

    private void navigateToMainActivity() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);

        Intent intent = new Intent(LoginActivity.this, MainActivity.class);
        intent.putExtra("user_id", preferences.getInt(PREF_USER_ID, -1));
        intent.putExtra("username", preferences.getString(PREF_USERNAME, ""));
        intent.putExtra("role", preferences.getString(PREF_USER_ROLE, ""));
        startActivity(intent);
        finish();
    }

    public static void logout(AppCompatActivity activity) {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(activity);
        SharedPreferences.Editor editor = preferences.edit();
        editor.putBoolean(PREF_IS_LOGGED_IN, false);
        editor.remove(PREF_USER_ID);
        editor.remove(PREF_USERNAME);
        editor.remove(PREF_USER_ROLE);
        editor.remove(PREF_NAMA_LENGKAP);
        editor.apply();

        // Redirect ke LoginActivity
        Intent intent = new Intent(activity, LoginActivity.class);
        activity.startActivity(intent);
        activity.finish();
    }

    private void initializeEntryAnimations() {
        // Animasi elemen login secara terpisah
        usernameEditText.setAlpha(0f);
        passwordEditText.setAlpha(0f);
        btnLogin.setAlpha(0f);
        btnForgotPassword.setAlpha(0f);

        usernameEditText.animate()
                .alpha(1f)
                .setStartDelay(300)
                .setDuration(500)
                .start();

        passwordEditText.animate()
                .alpha(1f)
                .setStartDelay(500)
                .setDuration(500)
                .start();

        btnLogin.animate()
                .alpha(1f)
                .setStartDelay(700)
                .setDuration(500)
                .start();

        btnForgotPassword.animate()
                .alpha(1f)
                .setStartDelay(900)
                .setDuration(500)
                .start();
    }

    private void animateLoginClick() {
        // Animasi pada saat tombol login ditekan
        ObjectAnimator scaleX = ObjectAnimator.ofFloat(btnLogin, "scaleX", 1f, 0.9f, 1f);
        ObjectAnimator scaleY = ObjectAnimator.ofFloat(btnLogin, "scaleY", 1f, 0.9f, 1f);
        ObjectAnimator rotation = ObjectAnimator.ofFloat(btnLogin, "rotation", 0f, 10f, -10f, 0f);

        AnimatorSet animatorSet = new AnimatorSet();
        animatorSet.setDuration(300);
        animatorSet.setInterpolator(new AccelerateDecelerateInterpolator());
        animatorSet.playTogether(scaleX, scaleY, rotation);
        animatorSet.start();
    }

    private void animateLoginSuccess() {
        // Animasi ketika login berhasil
        ObjectAnimator fadeOut = ObjectAnimator.ofFloat(usernameEditText, "alpha", 1f, 0f);
        fadeOut.setDuration(300);

        fadeOut.start();
    }

    private void login() {
        String username = usernameEditText.getText().toString().trim();
        String password = passwordEditText.getText().toString().trim();

        if (username.isEmpty() || password.isEmpty()) {
            Toast.makeText(LoginActivity.this, "Username dan Password harus diisi", Toast.LENGTH_SHORT).show();
            return;
        }

        // Membuat JSON request body
        Map<String, String> requestMap = new HashMap<>();
        requestMap.put("username", username);
        requestMap.put("password", password);
        String jsonBody = new Gson().toJson(requestMap);

        OkHttpClient client = new OkHttpClient();
        RequestBody body = RequestBody.create(JSON, jsonBody);

        Request request = new Request.Builder()
                .url(API_URL)
                .post(body)
                .addHeader("Content-Type", "application/json")
                .build();

        client.newCall(request).enqueue(new Callback() {
            @Override
            public void onFailure(Call call, IOException e) {
                runOnUiThread(() ->
                        Toast.makeText(LoginActivity.this,
                                "Kesalahan jaringan: " + e.getMessage(),
                                Toast.LENGTH_SHORT).show()
                );
            }

            @Override
            public void onResponse(Call call, Response response) throws IOException {
                final String responseBody = response.body().string();

                runOnUiThread(() -> {
                    try {
                        JSONObject jsonResponse = new JSONObject(responseBody);
                        boolean success = jsonResponse.getBoolean("success");
                        String message = jsonResponse.getString("message");

                        if (success) {
                            // Tambahkan animasi login berhasil
                            animateLoginSuccess();

                            // Parsing user data dari respons
                            JSONObject userObj = jsonResponse.getJSONObject("user");
                            int userId = userObj.getInt("id");
                            String userRole = userObj.getString("role");
                            String responseUsername = userObj.getString("username");

                            // Simpan informasi login
                            saveLoginInfo(userId, responseUsername, userRole);

                            // Tampilkan pesan sukses
                            Toast.makeText(LoginActivity.this, message, Toast.LENGTH_SHORT).show();

                            // Pindah ke MainActivity dengan sedikit delay untuk animasi
                            new Handler().postDelayed(() -> {
                                navigateToMainActivity();
                            }, 300);
                        } else {
                            // Animasi error
                            ObjectAnimator shake = ObjectAnimator.ofFloat(usernameEditText, "translationX", 0, -10, 10, -10, 10, 0);
                            shake.setDuration(300);
                            shake.start();

                            // Tampilkan pesan error dari server
                            Toast.makeText(LoginActivity.this, message, Toast.LENGTH_SHORT).show();
                        }
                    } catch (Exception e) {
                        // Error parsing JSON
                        Toast.makeText(LoginActivity.this,
                                "Kesalahan memproses respons: " + e.toString(),
                                Toast.LENGTH_LONG).show();

                        // Log error untuk debugging
                        e.printStackTrace();
                        System.out.println("Response Body: " + responseBody);
                    }
                });
            }
        });
    }
}