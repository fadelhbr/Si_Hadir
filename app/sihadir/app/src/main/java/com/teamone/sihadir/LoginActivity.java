package com.teamone.sihadir;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.preference.PreferenceManager;

import com.google.gson.Gson;
import okhttp3.*;
import java.io.IOException;
import org.json.JSONObject;
import java.util.HashMap;
import java.util.Map;

public class LoginActivity extends AppCompatActivity {

    private EditText usernameEditText, passwordEditText;
    private Button btnLogin;
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

        btnLogin.setOnClickListener(v -> login());
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
        editor.putInt("user_id", userId); // Menggunakan key yang sama dengan AbsenFragment
        editor.putString("username", username);
        editor.putString("user_role", userRole);
        editor.putString("nama_lengkap", displayName); // Key untuk nama yang akan ditampilkan
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

    public static void logout (AppCompatActivity activity) {
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
                            // Parsing user data dari respons
                            JSONObject userObj = jsonResponse.getJSONObject("user");
                            int userId = userObj.getInt("id");
                            String userRole = userObj.getString("role");
                            String responseUsername = userObj.getString("username");

                            // Simpan informasi login
                            saveLoginInfo(userId, responseUsername, userRole);

                            // Tampilkan pesan sukses
                            Toast.makeText(LoginActivity.this, message, Toast.LENGTH_SHORT).show();

                            // Pindah ke MainActivity
                            navigateToMainActivity();
                        } else {
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