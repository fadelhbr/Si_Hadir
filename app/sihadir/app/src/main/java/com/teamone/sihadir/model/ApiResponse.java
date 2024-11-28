package com.teamone.sihadir.model;

import java.util.List;

public class ApiResponse {
    private String status;
    private List<Riwayat> data;

    public String getStatus() {
        return status;
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
}
