package com.teamone.sihadir.model;

public class CutiRequest {
    private int pegawai_id;
    private String tanggal_mulai;
    private String tanggal_selesai;
    private String keterangan;

    public CutiRequest(int pegawai_id, String tanggal_mulai, String tanggal_selesai, String keterangan) {
        this.pegawai_id = pegawai_id;
        this.tanggal_mulai = tanggal_mulai;
        this.tanggal_selesai = tanggal_selesai;
        this.keterangan = keterangan;
    }

    // Getter dan Setter
    public int getPegawai_id() { return pegawai_id; }
    public void setPegawai_id(int pegawai_id) { this.pegawai_id = pegawai_id; }
    public String getTanggal_mulai() { return tanggal_mulai; }
    public void setTanggal_mulai(String tanggal_mulai) { this.tanggal_mulai = tanggal_mulai; }
    public String getTanggal_selesai() { return tanggal_selesai; }
    public void setTanggal_selesai(String tanggal_selesai) { this.tanggal_selesai = tanggal_selesai; }
    public String getKeterangan() { return keterangan; }
    public void setKeterangan(String keterangan) { this.keterangan = keterangan; }
}
