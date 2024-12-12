package com.teamone.sihadir.model;

public class ResetPasswordRequest {
    private String email;
    private String otp_code; // Kode OTP yang dikirim
    private String new_password; // Password baru yang ingin diset

    public ResetPasswordRequest(String email, String otp_code, String new_password) {
        this.email = email;
        this.otp_code = otp_code;
        this.new_password = new_password;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getOtp_code() {
        return otp_code;
    }

    public void setOtp_code(String otp_code) {
        this.otp_code = otp_code;
    }

    public String getNew_password() {
        return new_password;
    }

    public void setNew_password(String new_password) {
        this.new_password = new_password;
    }
}
