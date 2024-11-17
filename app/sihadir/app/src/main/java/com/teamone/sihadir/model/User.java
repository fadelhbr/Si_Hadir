package com.teamone.sihadir.model;

public class User {
    private String username;
    private String password;
    private String nama_lengkap;

    // Constructor
    public User(String username, String password, String nama_lengkap) {
        this.username = username;
        this.password = password;
        this.nama_lengkap = nama_lengkap;
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

    public void setNama(String nama_lengkap) {

        this.nama_lengkap = nama_lengkap;
    }

}
