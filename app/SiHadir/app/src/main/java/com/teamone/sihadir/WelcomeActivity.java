package com.example.hadir;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.view.animation.Animation;
import android.view.animation.AnimationUtils;
import android.widget.Button;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

import com.example.hadir.R;

public class WelcomeActivity extends AppCompatActivity {

    private Button btnStart;
    private TextView txtWelcome;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_welcome);

        btnStart = findViewById(R.id.btnStart);
        txtWelcome = findViewById(R.id.txtWelcome);

        TextView txtWelcome = findViewById(R.id.txtWelcome);
        Animation fadeIn = AnimationUtils.loadAnimation(this, R.anim.fade_in);
        txtWelcome.startAnimation(fadeIn);


        btnStart.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                v.startAnimation(AnimationUtils.loadAnimation(WelcomeActivity.this, R.anim.button_scale));
                // Pindah ke Activity Register
                Intent intent = new Intent(WelcomeActivity.this, LoginActivity.class);
                startActivity(intent);
            }
        });

    };
}

