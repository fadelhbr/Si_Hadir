package com.teamone.sihadir;

import android.animation.AnimatorSet;
import android.animation.ObjectAnimator;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
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
import android.util.Base64;

public class LoginActivity extends AppCompatActivity {

    private EditText usernameEditText, passwordEditText;
    private Button btnLogin;
    private static final String API_URL = "http://192.168.1.37/Si_Hadir/web/sihadir/app/api/api_login.php";
    private static final MediaType JSON = MediaType.parse("application/json; charset=utf-8");

    // SharedPreferences keys
    private static final String PREF_IS_LOGGED_IN = "is_logged_in";
    private static final String PREF_USER_ID = "user_id";
    private static final String PREF_USERNAME = "username";
    private static final String PREF_PASSWORD = "password";
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

    private void saveLoginInfo(int userId, String username, String password, String userRole) {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);
        SharedPreferences.Editor editor = preferences.edit();

        // Encrypt password
        String encryptedPassword = encryptPassword(password);

        // Capitalize username for display
        String displayName = username.substring(0, 1).toUpperCase() + username.substring(1).toLowerCase();

        // Save all credentials
        editor.putBoolean(PREF_IS_LOGGED_IN, true);
        editor.putInt(PREF_USER_ID, userId);
        editor.putString(PREF_USERNAME, username);
        editor.putString(PREF_PASSWORD, encryptedPassword);
        editor.putString(PREF_USER_ROLE, userRole);
        editor.putString(PREF_NAMA_LENGKAP, displayName);

        // Verify that editor.apply() is successful
        boolean success = editor.commit(); // Using commit() instead of apply() to verify

        // Log the save operation
        Log.d("LOGIN_DEBUG", "Save credentials success: " + success);
        Log.d("LOGIN_DEBUG", "Username saved: " + preferences.getString(PREF_USERNAME, "not found"));
        Log.d("LOGIN_DEBUG", "Password saved: " + (preferences.getString(PREF_PASSWORD, null) != null));
    }

    private String encryptPassword(String password) {
        return Base64.encodeToString(password.getBytes(), Base64.DEFAULT);
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

    private void login() {
        String username = usernameEditText.getText().toString().trim();
        String password = passwordEditText.getText().toString().trim();

        if (username.isEmpty() || password.isEmpty()) {
            Toast.makeText(LoginActivity.this, "Username dan Password harus diisi", Toast.LENGTH_SHORT).show();
            return;
        }

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
                            JSONObject userObj = jsonResponse.getJSONObject("user");
                            int userId = userObj.getInt("id");
                            String userRole = userObj.getString("role");
                            String responseUsername = userObj.getString("username");

                            // Save login info including password
                            saveLoginInfo(userId, responseUsername, password, userRole);

                            // Verify saved credentials immediately after saving
                            verifyCredentialsSaved();

                            Toast.makeText(LoginActivity.this, message, Toast.LENGTH_SHORT).show();
                            navigateToMainActivity();
                        } else {
                            Toast.makeText(LoginActivity.this, message, Toast.LENGTH_SHORT).show();
                        }
                    } catch (Exception e) {
                        Toast.makeText(LoginActivity.this,
                                "Kesalahan memproses respons: " + e.toString(),
                                Toast.LENGTH_LONG).show();
                        e.printStackTrace();
                    }
                });
            }
        });
    }

    // Method baru untuk verifikasi credentials
    private void verifyCredentialsSaved() {
        SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(this);
        boolean isLoggedIn = preferences.getBoolean(PREF_IS_LOGGED_IN, false);
        String savedUsername = preferences.getString(PREF_USERNAME, "");
        String savedPassword = preferences.getString(PREF_PASSWORD, "");

        Log.d("LOGIN_VERIFY", "Is Logged In: " + isLoggedIn);
        Log.d("LOGIN_VERIFY", "Username saved: " + !savedUsername.isEmpty());
        Log.d("LOGIN_VERIFY", "Password saved: " + !savedPassword.isEmpty());
    }
}