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

import java.util.List;

public class RiwayatAdapter extends RecyclerView.Adapter<RiwayatAdapter.ViewHolder> {

    private List<Riwayat> riwayatList;

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

    // Metode untuk memformat string (menghapus underscore dan mengkapitalisasi)
    private String formatString(String input) {
        if (input == null || input.isEmpty()) {
            return "-";
        }

        // Menghapus underscore dan menggantinya dengan spasi
        String replaced = input.replace("_", " ");

        // Mengkapitalisasi huruf pertama setiap kata
        StringBuilder formatted = new StringBuilder();
        String[] words = replaced.split(" ");
        for (String word : words) {
            if (!word.isEmpty()) {
                formatted.append(Character.toUpperCase(word.charAt(0)))
                        .append(word.substring(1).toLowerCase())
                        .append(" ");
            }
        }

        return formatted.toString().trim();
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
}