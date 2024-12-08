package com.teamone.sihadir.model;

public class OtpVerificationRequest {
    private String email;
    private String otp_code;

    public OtpVerificationRequest(String email, String otp_code) {
        this.email = email;
        this.otp_code = otp_code;
    }

    public String getEmail() {
        return email;
    }

    public String getOtp_code() {
        return otp_code;
    }
}
