package com.teamone.sihadir.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.teamone.sihadir.R;
import com.teamone.sihadir.model.RiwayatCuti;

import java.util.List;

public class RiwayatCutiAdapter extends RecyclerView.Adapter<RiwayatCutiAdapter.RiwayatCutiViewHolder> {

    private List<RiwayatCuti> riwayatCutiList;

    public RiwayatCutiAdapter(List<RiwayatCuti> riwayatCutiList) {
        this.riwayatCutiList = riwayatCutiList;
    }

    @NonNull
    @Override
    public RiwayatCutiViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_riwayat_cuti, parent, false);
        return new RiwayatCutiViewHolder(view);
    }


    @Override
    public void onBindViewHolder(@NonNull RiwayatCutiViewHolder holder, int position) {

        if (position % 2 == 0) {
            holder.itemView.setBackgroundColor(ContextCompat.getColor(holder.itemView.getContext(), R.color.md_theme_light_surface)); // Even rows
        } else {
            holder.itemView.setBackgroundColor(ContextCompat.getColor(holder.itemView.getContext(), R.color.md_theme_light_surfaceVariant)); // Odd rows
        }

        RiwayatCuti riwayatCuti = riwayatCutiList.get(position);

        // Memformat data sebelum dikirim ke ViewHolder
        String tanggalMulai = formatText(riwayatCuti.getTanggal_mulai());
        String tanggalSelesai = formatText(riwayatCuti.getTanggal_selesai());
        String keterangan = formatText(riwayatCuti.getKeterangan());
        String status = formatText(riwayatCuti.getStatus());

        // Mengirim data yang telah diformat ke bind() di ViewHolder
        holder.bind(tanggalMulai, tanggalSelesai, keterangan, status);
    }


    @Override
    public int getItemCount() {
        return riwayatCutiList.size();
    }

    private String formatText(String text) {
        if (text == null) {
            return "";
        }
        // Ganti underscore dengan spasi dan kapitalisasi setiap kata
        String formattedText = text.replace("_", " ");
        String[] words = formattedText.split(" ");
        StringBuilder capitalizedText = new StringBuilder();
        for (String word : words) {
            if (!word.isEmpty()) {
                capitalizedText.append(word.substring(0, 1).toUpperCase());
                capitalizedText.append(word.substring(1).toLowerCase());
                capitalizedText.append(" ");
            }
        }
        return capitalizedText.toString().trim();
    }

    public void updateData(List<RiwayatCuti> newData) {
        riwayatCutiList.clear();
        riwayatCutiList.addAll(newData);
        notifyDataSetChanged();
    }

    public class RiwayatCutiViewHolder extends RecyclerView.ViewHolder {
        private TextView tvTanggalMulaiCuti;
        private TextView tvTanggalSelesaiCuti;
        private TextView tvKeteranganCuti;
        private TextView tvStatusCuti;

        public RiwayatCutiViewHolder(View itemView) {
            super(itemView);
            tvTanggalMulaiCuti = itemView.findViewById(R.id.tvTanggalMulaiCuti);
            tvTanggalSelesaiCuti = itemView.findViewById(R.id.tvTanggalSelesaiCuti);
            tvKeteranganCuti = itemView.findViewById(R.id.tvKeteranganCuti);
            tvStatusCuti = itemView.findViewById(R.id.tvStatusCuti);
        }

        // Method to bind formatted data to the view
        public void bind(String tanggalMulai, String tanggalSelesai, String keterangan, String status) {
            tvTanggalMulaiCuti.setText(tanggalMulai);
            tvTanggalSelesaiCuti.setText(tanggalSelesai);
            tvKeteranganCuti.setText(keterangan);
            tvStatusCuti.setText(status);
        }
    }
}
