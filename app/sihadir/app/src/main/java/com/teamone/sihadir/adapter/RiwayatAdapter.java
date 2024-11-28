package com.teamone.sihadir.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.teamone.sihadir.R;
import com.teamone.sihadir.model.Riwayat;

import java.util.ArrayList;
import java.util.List;

public class RiwayatAdapter extends RecyclerView.Adapter<RiwayatAdapter.ViewHolder> {

    private List<Riwayat> riwayatList;
    private static final int PAGE_SIZE = 5; // Menentukan ukuran halaman
    private int currentPage = 0; // Halaman yang sedang ditampilkan

    public RiwayatAdapter(List<Riwayat> riwayatList) {
        this.riwayatList = riwayatList;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_riwayat, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        // Ganti warna baris berdasarkan posisi (genap atau ganjil)
        if (position % 2 == 0) {
            holder.itemView.setBackgroundColor(ContextCompat.getColor(holder.itemView.getContext(), R.color.md_theme_light_surface)); // Even rows
        } else {
            holder.itemView.setBackgroundColor(ContextCompat.getColor(holder.itemView.getContext(), R.color.md_theme_light_surfaceVariant)); // Odd rows
        }

        Riwayat riwayat = riwayatList.get(position);

        // Format data untuk menghapus underscore dan kapitalisasi huruf pertama
        String formattedTanggal = formatString(riwayat.getTanggal());
        String formattedJadwalShift = formatString(riwayat.getJadwalShift());
        String formattedWaktuMasuk = formatString(riwayat.getWaktuMasuk());
        String formattedWaktuKeluar = formatString(riwayat.getWaktuKeluar());
        String formattedStatus = formatString(riwayat.getStatusKehadiran());

        // Set data yang telah diformat ke TextView
        holder.tvTanggal.setText(formattedTanggal);
        holder.tvJadwalShift.setText(formattedJadwalShift);
        holder.tvWaktuMasuk.setText(formattedWaktuMasuk);
        holder.tvWaktuKeluar.setText(formattedWaktuKeluar);
        holder.tvStatus.setText(formattedStatus);
    }

    @Override
    public int getItemCount() {
        return riwayatList.size();
    }

    // Menambahkan data lebih lanjut ke dalam daftar
    public void addData(List<Riwayat> newRiwayatList) {
        int startPos = riwayatList.size();
        riwayatList.addAll(newRiwayatList);
        notifyItemRangeInserted(startPos, newRiwayatList.size());
    }

    // ViewHolder untuk item data riwayat
    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTanggal, tvJadwalShift, tvWaktuMasuk, tvWaktuKeluar, tvStatus;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTanggal = itemView.findViewById(R.id.tvTanggal);
            tvJadwalShift = itemView.findViewById(R.id.tvJadwalShift);
            tvWaktuMasuk = itemView.findViewById(R.id.tvWaktuMasuk);
            tvWaktuKeluar = itemView.findViewById(R.id.tvWaktuKeluar);
            tvStatus = itemView.findViewById(R.id.tvStatusKehadiran);
        }
    }

    // Metode untuk mendapatkan data berdasarkan halaman dan ukuran halaman
    public List<Riwayat> getDataForPage(int page) {
        int startIndex = page * PAGE_SIZE;
        int endIndex = Math.min(startIndex + PAGE_SIZE, riwayatList.size());

        return new ArrayList<>(riwayatList.subList(startIndex, endIndex));
    }

    // Fungsi untuk memformat string: menghapus underscore dan kapitalisasi huruf pertama
    private String formatString(String input) {
        if (input == null) {
            return "";
        }

        // Ganti underscore dengan spasi
        String result = input.replace("_", " ");

        // Kapitalisasi huruf pertama dari setiap kata
        StringBuilder formattedString = new StringBuilder();
        String[] words = result.split(" ");
        for (String word : words) {
            if (word.length() > 0) {
                formattedString.append(word.substring(0, 1).toUpperCase());
                formattedString.append(word.substring(1).toLowerCase());
            }
            formattedString.append(" ");
        }

        return formattedString.toString().trim();
    }
}
