package com.teamone.sihadir.model;

import java.util.List;

public class ApiResponse {
    private String message;
    private boolean success;
    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }
    private String status;
    private List<Riwayat> data;

    public String getStatus() {return status;
    }
    public void setStatus(String status) {
        this.status = status;
    }

    public List<Riwayat> getData() {
        return data;
    }

    public void setData(List<Riwayat> data) {
        this.data = data;
    }

    public boolean isSuccess() {
        return success;
    }

    public void setSuccess(boolean success) {
        this.success = success;
    }
}