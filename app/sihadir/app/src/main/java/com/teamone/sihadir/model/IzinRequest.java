package com.teamone.sihadir.model;

public class IzinRequest {
    private int pegawai_id;
    private String tanggal;
    private String jenis_izin;
    private String keterangan;

    // Constructor
    public IzinRequest(int pegawai_id, String tanggal, String jenis_izin, String keterangan) {
        this.pegawai_id = pegawai_id;
        this.tanggal = tanggal;
        this.jenis_izin = jenis_izin;
        this.keterangan = keterangan;
    }

    // Getter dan Setter
    public int getPegawai_id() {
        return pegawai_id;
    }

    public void setPegawai_id(int pegawai_id) {
        this.pegawai_id = pegawai_id;
    }

    public String getTanggal() {
        return tanggal;
    }

    public void setTanggal(String tanggal) {
        this.tanggal = tanggal;
    }

    public String getJenis_izin() {
        return jenis_izin;
    }

    public void setJenis_izin(String jenis_izin) {
        this.jenis_izin = jenis_izin;
    }

    public String getKeterangan() {
        return keterangan;
    }

    public void setKeterangan(String keterangan) {
        this.keterangan = keterangan;
    }
}
