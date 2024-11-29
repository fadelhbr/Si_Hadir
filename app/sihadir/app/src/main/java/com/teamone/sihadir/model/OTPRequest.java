package com.teamone.sihadir.model;

public class OTPRequest {
    private String email;
    private String otpCode;

    public OTPRequest(String email, String otpCode) {
        this.email = email;
        this.otpCode = otpCode;
    }

    public String getEmail() {
        return email;
    }

    public String getOtpCode() {
        return otpCode;
    }
}
