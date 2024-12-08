package com.teamone.sihadir.model;

import java.util.List;

public class RiwayatCutiResponse {
    private String status;
    private String message;
    private List<RiwayatCuti> data;

    // Getters and Setters
    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    public List<RiwayatCuti> getData() {
        return data;
    }

    public void setData(List<RiwayatCuti> data) {
        this.data = data;
    }

    public static class Cuti {
        private int id;
        private int pegawai_id;
        private String tanggal_mulai;
        private String tanggal_selesai;
        private String status;

        // Getters and Setters
        public int getId() {
            return id;
        }

        public void setId(int id) {
            this.id = id;
        }

        public int getPegawai_id() {
            return pegawai_id;
        }

        public void setPegawai_id(int pegawai_id) {
            this.pegawai_id = pegawai_id;
        }

        public String getTanggal_mulai() {
            return tanggal_mulai;
        }

        public void setTanggal_mulai(String tanggal_mulai) {
            this.tanggal_mulai = tanggal_mulai;
        }

        public String getTanggal_selesai() {
            return tanggal_selesai;
        }

        public void setTanggal_selesai(String tanggal_selesai) {
            this.tanggal_selesai = tanggal_selesai;
        }

        public String getStatus() {
            return status;
        }

        public void setStatus(String status) {
            this.status = status;
        }
    }
}
