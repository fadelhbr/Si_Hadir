package com.teamone.sihadir.model;

public class AttendanceStatusResponse {
    private boolean success;
    private Attendance attendance;

    public boolean isSuccess() {
        return success;
    }

    public Attendance getAttendance() {
        return attendance;
    }

    public static class Attendance {
        private String tanggal;
        private String status_kehadiran;
        private String keterangan;

        public String getTanggal() {
            return tanggal;
        }

        public String getStatusKehadiran() {
            return status_kehadiran;
        }

        public String getKeterangan() {
            return keterangan;
        }
    }
}