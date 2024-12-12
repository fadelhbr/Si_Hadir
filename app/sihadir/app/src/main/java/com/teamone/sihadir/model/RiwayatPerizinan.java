package com.teamone.sihadir.model;

public class RiwayatPerizinan {
    private RiwayatCuti riwayatCuti;
    private RiwayatIzin riwayatIzin;

    // Constructor, getter, and setter methods
    public RiwayatPerizinan(RiwayatCuti riwayatCuti, RiwayatIzin riwayatIzin) {
        this.riwayatCuti = riwayatCuti;
        this.riwayatIzin = riwayatIzin;
    }

    public RiwayatCuti getRiwayatCuti() {
        return riwayatCuti;
    }

    public void setRiwayatCuti(RiwayatCuti riwayatCuti) {
        this.riwayatCuti = riwayatCuti;
    }

    public RiwayatIzin getRiwayatIzin() {
        return riwayatIzin;
    }

    public void setRiwayatIzin(RiwayatIzin riwayatIzin) {
        this.riwayatIzin = riwayatIzin;
    }
}
