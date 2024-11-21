package com.teamone.sihadir.model;

public class User {
    private String username;
    private String password;
    private String nama_lengkap;
    private Integer employee_id;

    // Constructor
    public User(String username, String password, String nama_lengkap, Integer employee_id) {
        this.username = username;
        this.password = password;
        this.nama_lengkap = nama_lengkap;
        this.employee_id = employee_id;
    }

    // Getter dan Setter

    public String getUsername() {

        return username;
    }

    public void setUsername(String username) {

        this.username = username;
    }

    public String getPassword() {

        return password;
    }

    public void setPassword(String password) {

        this.password = password;
    }

    public String getNama_lengkap() {

        return nama_lengkap;
    }

    public void setNama_lengkap(String nama_lengkap) {

        this.nama_lengkap = nama_lengkap;
    }

    public Integer getEmployee_id() {

        return employee_id;
    }

    public void setEmployee_id(Integer employee_id) {

        this.employee_id = employee_id;
    }
}
