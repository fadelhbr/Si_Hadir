package com.teamone.sihadir.model;

import java.util.Date;

public class Kehadiran {
    private Date tanggal;
    private String status;
    private String jamMasuk;
    private String jamKeluar;

    public Kehadiran(Date tanggal, String status, String jamMasuk, String jamKeluar) {
        this.tanggal = tanggal;
        this.status = status;
        this.jamMasuk = jamMasuk;
        this.jamKeluar = jamKeluar;
    }

    public Date getTanggal() {
        return tanggal;
    }

    public void setTanggal(Date tanggal) {
        this.tanggal = tanggal;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getJamMasuk() {
        return jamMasuk;
    }

    public void setJamMasuk(String jamMasuk) {
        this.jamMasuk = jamMasuk;
    }

    public String getJamKeluar() {
        return jamKeluar;
    }

    public void setJamKeluar(String jamKeluar) {
        this.jamKeluar = jamKeluar;
    }
}