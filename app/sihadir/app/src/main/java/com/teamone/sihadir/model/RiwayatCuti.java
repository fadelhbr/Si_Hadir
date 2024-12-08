package com.teamone.sihadir.model;

public class RiwayatCuti {
    private String tanggal_mulai;
    private String tanggal_selesai;
    private String keterangan;
    private String status;

    // Constructor
    public RiwayatCuti(String tanggal_mulai, String tanggal_selesai, String keterangan, String status) {
        this.tanggal_mulai = tanggal_mulai;
        this.tanggal_selesai = tanggal_selesai;
        this.keterangan = keterangan;
        this.status = status;
    }

    // Getter methods
    public String getTanggal_mulai() {
        return tanggal_mulai;
    }

    public String getTanggal_selesai() {
        return tanggal_selesai;
    }

    public String getKeterangan() {
        return keterangan;
    }

    public String getStatus() {
        return status;
    }

    // Setter methods
    public void setTanggal_mulai(String tanggal_mulai) {
        this.tanggal_mulai = tanggal_mulai;
    }

    public void setTanggal_selesai(String tanggal_selesai) {
        this.tanggal_selesai = tanggal_selesai;
    }

    public void setKeterangan(String keterangan) {
        this.keterangan = keterangan;
    }

    public void setStatus(String status) {
        this.status = status;
    }
}
