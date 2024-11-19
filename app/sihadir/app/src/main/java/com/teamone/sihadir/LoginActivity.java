package com.teamone.sihadir;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.google.gson.Gson;
import com.google.gson.JsonSyntaxException;
import com.teamone.sihadir.model.ApiResponse;

import okhttp3.Call;
import okhttp3.Callback;
import okhttp3.FormBody;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;
import java.io.IOException;

public class LoginActivity extends AppCompatActivity {
    private static final String TAG = "LoginActivity";
    private static final String API_URL = "http://10.10.5.130/Si_Hadir/web/sihadir/app/api/api_login.php";

    private EditText usernameEditText;
    private EditText passwordEditText;
    private Button btnLogin;
    private OkHttpClient client;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        initializeViews();
        setupClickListeners();
        client = new OkHttpClient();
    }

    private void initializeViews() {
        usernameEditText = findViewById(R.id.txtUsername);
        passwordEditText = findViewById(R.id.txtPassword);
        btnLogin = findViewById(R.id.btnLogin);
    }

    private void setupClickListeners() {
        btnLogin.setOnClickListener(v -> attemptLogin());
    }

    private void attemptLogin() {
        String username = usernameEditText.getText().toString().trim();
        String password = passwordEditText.getText().toString().trim();

        if (!validateInput(username, password)) {
            return;
        }

        performLogin(username, password);
    }

    private boolean validateInput(String username, String password) {
        if (username.isEmpty()) {
            showToast("Username cannot be empty");
            usernameEditText.requestFocus();
            return false;
        }
        if (password.isEmpty()) {
            showToast("Password cannot be empty");
            passwordEditText.requestFocus();
            return false;
        }
        return true;
    }

    private void performLogin(String username, String password) {
        RequestBody formBody = new FormBody.Builder()
                .add("username", username)
                .add("password", password)
                .build();

        Request request = new Request.Builder()
                .url(API_URL)
                .post(formBody)
                .build();

        client.newCall(request).enqueue(new Callback() {
            @Override
            public void onFailure(Call call, IOException e) {
                Log.e(TAG, "Login network error", e);
                showToastOnUiThread("Network error: " + e.getMessage());
            }

            @Override
            public void onResponse(Call call, Response response) throws IOException {
                if (!response.isSuccessful()) {
                    showToastOnUiThread("Server error: " + response.code());
                    return;
                }

                try {
                    String responseBody = response.body().string();
                    Log.d(TAG, "Login response: " + responseBody);
                    handleLoginResponse(responseBody);
                } catch (IOException e) {
                    Log.e(TAG, "Error reading response", e);
                    showToastOnUiThread("Error reading server response");
                } finally {
                    response.close();
                }
            }
        });
    }

    private void handleLoginResponse(String responseBody) {
        try {
            Gson gson = new Gson();
            ApiResponse apiResponse = gson.fromJson(responseBody, ApiResponse.class);

            if (apiResponse == null) {
                showToastOnUiThread("Invalid server response");
                return;
            }

            if ("success".equals(apiResponse.getStatus())) {
                handleSuccessfulLogin(apiResponse);
            } else {
                showToastOnUiThread(apiResponse.getMessage());
            }
        } catch (JsonSyntaxException e) {
            Log.e(TAG, "Error parsing JSON response", e);
            showToastOnUiThread("Error processing server response");
        }
    }

    private void handleSuccessfulLogin(ApiResponse apiResponse) {
        runOnUiThread(() -> {
            showToast(apiResponse.getMessage());

            Intent intent = new Intent(LoginActivity.this, MainActivity.class);
            intent.putExtra("nama_lengkap", apiResponse.getNama_lengkap());
            intent.putExtra("role", apiResponse.getRole());
            startActivity(intent);
            finish();
        });
    }

    private void showToast(String message) {
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show();
    }

    private void showToastOnUiThread(String message) {
        runOnUiThread(() -> showToast(message));
    }
}