package com.teamone.sihadir.model;

import com.google.gson.annotations.SerializedName;

public class ApiResponse {
    @SerializedName("status")
    private String status;

    @SerializedName("message")
    private String message;

    @SerializedName("nama_lengkap")
    private String nama_lengkap;

    @SerializedName("role")
    private String role;

    // Getter dan Setter

    public String getStatus() {

        return status;
    }

    public void setStatus(String status) {

        this.status = status;
    }

    public String getMessage() {

        return message;
    }

    public void setMessage(String message) {

        this.message = message;
    }

    public String getNama_lengkap(){

        return nama_lengkap;
    }

    public void setNama_lengkap(String nama_lengkap) {
        this.nama_lengkap = nama_lengkap;
    }

    public void setRole(String role) {
        this.role = role;
    }


    public  void setRole(){
        this.role = role;
    }

    public String getRole() {
        return null;
    }
}

