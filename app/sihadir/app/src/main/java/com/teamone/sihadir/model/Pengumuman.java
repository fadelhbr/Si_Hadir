package com.teamone.sihadir.model;

public class Pengumuman {
    private String id;
    private String judul;
    private String isi;
    private String tanggal;

    public Pengumuman(String id, String judul, String isi, String tanggal) {
        this.id = id;
        this.judul = judul;
        this.isi = isi;
        this.tanggal = tanggal;
    }

    public String getId() {
        return id;
    }

    public void setId(String id) {
        this.id = id;
    }

    public String getJudul() {
        return judul;
    }

    public void setJudul(String judul) {
        this.judul = judul;
    }

    public String getIsi() {
        return isi;
    }

    public void setIsi(String isi) {
        this.isi = isi;
    }

    public String getTanggal() {
        return tanggal;
    }

    public void setTanggal(String tanggal) {
        this.tanggal = tanggal;
    }
}