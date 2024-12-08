package com.teamone.sihadir.model;

public class RiwayatIzin {
    private int id;
    private String tanggal;
    private String jenis_izin;
    private String keterangan;
    private String status;

    // Getters and Setters
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getTanggal() {
        return tanggal;
    }

    public void setTanggal(String tanggal) {
        this.tanggal = tanggal;
    }

    public String getJenisIzin() {
        return jenis_izin;
    }

    public void setJenisIzin(String jenis_izin) {
        this.jenis_izin = jenis_izin;
    }

    public String getKeterangan() {
        return keterangan;
    }

    public void setKeterangan(String keterangan) {
        this.keterangan = keterangan;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }
}
