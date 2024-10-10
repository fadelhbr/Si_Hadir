package com.example.hadir.model;

public class User {
    private String username;
    private String password;

    // Constructor
    public User(String namaLengkap, String username, String password) {
        this.username = username;
        this.password = password;
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
}
