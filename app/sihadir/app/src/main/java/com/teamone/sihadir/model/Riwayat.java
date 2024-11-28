package com.teamone.sihadir.model;

public class Riwayat {
    private String tanggal;
    private String jadwal_shift;
    private String waktu_masuk;
    private String waktu_keluar;
    private String status_kehadiran;

    // Getter dan Setter
    public String getTanggal() {
        return tanggal;
    }

    public void setTanggal(String tanggal) {
        this.tanggal = tanggal;
    }

    public String getJadwalShift() {
        return jadwal_shift;
    }

    public void setJadwalShift(String jadwal_shift) {
        this.jadwal_shift = jadwal_shift;
    }

    public String getWaktuMasuk() {
        return waktu_masuk;
    }

    public void setWaktuMasuk(String waktu_masuk) {
        this.waktu_masuk = waktu_masuk;
    }

    public String getWaktuKeluar() {
        return waktu_keluar;
    }

    public void setWaktuKeluar(String waktu_keluar) {
        this.waktu_keluar = waktu_keluar;
    }

    public String getStatusKehadiran() {
        return status_kehadiran;
    }

    public void setStatusKehadiran(String status_kehadiran) {
        this.status_kehadiran = status_kehadiran;
    }
}
