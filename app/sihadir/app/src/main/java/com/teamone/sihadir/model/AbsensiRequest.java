package com.teamone.sihadir.model;

public class AbsensiRequest {
    private String user_id; // Sesuaikan dengan format API
    private String unique_code; // Sesuaikan dengan format API
    private boolean confirm_early_leave; // Tambahkan sesuai API

    public AbsensiRequest(String user_id, String unique_code, boolean confirm_early_leave) {
        this.user_id = user_id;
        this.unique_code = unique_code;
        this.confirm_early_leave = confirm_early_leave;
    }

    // Getters dan Setters
    public String getUser_id() {
        return user_id;
    }

    public void setUser_id(String user_id) {
        this.user_id = user_id;
    }

    public String getUnique_code() {
        return unique_code;
    }

    public void setUnique_code(String unique_code) {
        this.unique_code = unique_code;
    }

    public boolean isConfirm_early_leave() {
        return confirm_early_leave;
    }

    public void setConfirm_early_leave(boolean confirm_early_leave) {
        this.confirm_early_leave = confirm_early_leave;
    }
}
