package com.teamone.sihadir.model;

import java.util.List;

public class RiwayatIzinResponse {
    private String status;
    private String message;
    private List<RiwayatIzin> data;

    // Getters and Setters
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

    public List<RiwayatIzin> getData() {
        return data;
    }

    public void setData(List<RiwayatIzin> data) {
        this.data = data;
    }
}
