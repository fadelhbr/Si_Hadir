package com.teamone.sihadir;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.google.gson.Gson;
import com.teamone.sihadir.model.ApiResponse; // Import model ApiResponse

import okhttp3.Call;
import okhttp3.Callback;
import okhttp3.FormBody;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;
import java.io.IOException;

public class LoginActivity extends AppCompatActivity {

    private EditText usernameEditText, passwordEditText;
    private Button btnLogin;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        // Inisialisasi komponen
        usernameEditText = findViewById(R.id.txtUsername);
        passwordEditText = findViewById(R.id.txtPassword);
        btnLogin = findViewById(R.id.btnLogin);

        // Ketika tombol login diklik
        btnLogin.setOnClickListener(v -> login());
    }

    // Fungsi login
    private void login() {
        String username = usernameEditText.getText().toString();
        String password = passwordEditText.getText().toString();

        // Validasi input kosong
        if (username.isEmpty() || password.isEmpty()) {
            Toast.makeText(LoginActivity.this, "Username dan Password harus diisi", Toast.LENGTH_SHORT).show();
            return;
        }

        OkHttpClient client = new OkHttpClient();

        // Membuat RequestBody dengan data username dan password
        RequestBody formBody = new FormBody.Builder()
                .add("username", username)
                .add("password", password)
                .build();

        // Membuat request POST
        Request request = new Request.Builder()
                .url("http://192.168.18.236/sihadir/app/api/api_login.php") // Ganti dengan URL API kamu
                .post(formBody)
                .build();

        // Mengirim request ke server
        client.newCall(request).enqueue(new Callback() {
            @Override
            public void onFailure(Call call, IOException e) {
                // Menampilkan pesan error jika terjadi kegagalan jaringan
                runOnUiThread(() ->
                        Toast.makeText(LoginActivity.this, "Network error: " + e.getMessage(), Toast.LENGTH_SHORT).show()
                );
            }

            @Override
            public void onResponse(Call call, Response response) throws IOException {
                if (response.isSuccessful()) {
                    String jsonResponse = response.body().string(); // Mendapatkan string JSON dari respons

                    // Parsing respons JSON menggunakan Gson
                    Gson gson = new Gson();
                    ApiResponse apiResponse = gson.fromJson(jsonResponse, ApiResponse.class);
                    String nama_lengkap = apiResponse.getNama_lengkap(); // Ambil nama lengkap dari API respons
                    String role = apiResponse.getRole();

                    if (apiResponse.getStatus().equals("success")) {
                        // Jika login berhasil, pindah ke MainActivity dan kirim nama
                        runOnUiThread(() -> {
                            Toast.makeText(LoginActivity.this, apiResponse.getMessage(), Toast.LENGTH_SHORT).show();

                            // Intent untuk pindah ke MainActivity
                            Intent intent = new Intent(LoginActivity.this, MainActivity.class);
                            intent.putExtra("nama_lengkap", nama_lengkap); // Mengirim nama lengkap ke MainActivity
                            intent.putExtra("role", role);
                            startActivity(intent);
                            finish(); // Menutup LoginActivity
                        });
                    } else {
                        // Jika login gagal, tampilkan pesan dari API
                        runOnUiThread(() ->
                                Toast.makeText(LoginActivity.this, apiResponse.getMessage(), Toast.LENGTH_SHORT).show()
                        );
                    }
                } else {
                    // Jika respons gagal, tampilkan pesan gagal login
                    runOnUiThread(() ->
                            Toast.makeText(LoginActivity.this, "Login failed!", Toast.LENGTH_SHORT).show()
                    );
                }
            }
        });
    }
}
