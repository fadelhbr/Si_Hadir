package com.teamone.sihadir.model;

public class AbsensiApiResponse {
    private String status;
    private String message;
    private AbsensiDetail data;

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

    public AbsensiDetail getData() {
        return data;
    }

    public void setData(AbsensiDetail data) {
        this.data = data;
    }

    // Inner class to represent absensi details
    public static class AbsensiDetail {
        private String timestamp;
        private String location;
        private String unique_code;

        public String getTimestamp() {
            return timestamp;
        }

        public void setTimestamp(String timestamp) {
            this.timestamp = timestamp;
        }

        public String getLocation() {
            return location;
        }

        public void setLocation(String location) {
            this.location = location;
        }

        public String getUnique_code() {
            return unique_code;
        }

        public void setUnique_code(String unique_code) {
            this.unique_code = unique_code;
        }
    }
}