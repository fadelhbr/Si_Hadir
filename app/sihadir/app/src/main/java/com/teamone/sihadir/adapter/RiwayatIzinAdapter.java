package com.teamone.sihadir.adapter;

import android.content.res.Resources;
import android.util.TypedValue;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.RecyclerView;

import com.teamone.sihadir.R;
import com.teamone.sihadir.model.RiwayatIzin;

import java.util.List;

public class RiwayatIzinAdapter extends RecyclerView.Adapter<RiwayatIzinAdapter.RiwayatIzinViewHolder> {

    private List<RiwayatIzin> riwayatIzinList;

    public RiwayatIzinAdapter(List<RiwayatIzin> riwayatIzinList) {
        this.riwayatIzinList = riwayatIzinList;
    }

    @NonNull
    @Override
    public RiwayatIzinViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_riwayat_izin, parent, false);
        return new RiwayatIzinViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull RiwayatIzinViewHolder holder, int position) {

        TypedValue typedValue = new TypedValue();
        Resources.Theme theme = holder.itemView.getContext().getTheme();

        // Default background
        theme.resolveAttribute(com.google.android.material.R.attr.colorSurface, typedValue, true);
        holder.itemView.setBackgroundColor(typedValue.data);

        // Alternate background color for even/odd rows
        if (position % 2 == 1) {
            theme.resolveAttribute(com.google.android.material.R.attr.colorSurfaceVariant, typedValue, true);
            holder.itemView.setBackgroundColor(typedValue.data);
        }

        RiwayatIzin riwayatIzin = riwayatIzinList.get(position);

        String tanggal = formatText(riwayatIzin.getTanggal());
        String jenisIzin = formatText(riwayatIzin.getJenisIzin());
        String keterangan = formatText(riwayatIzin.getKeterangan());
        String status = formatText(riwayatIzin.getStatus());

        // Menampilkan data pada TextView
        holder.tvTanggalIzin.setText(tanggal);
        holder.tvJenisIzin.setText(jenisIzin);
        holder.tvKeteranganIzin.setText(keterangan);
        holder.tvStatusIzin.setText(status);
    }

    @Override
    public int getItemCount() {
        return riwayatIzinList.size();
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

    public void updateData(List<RiwayatIzin> newRiwayatIzinList) {
        riwayatIzinList.clear();
        riwayatIzinList.addAll(newRiwayatIzinList);
        notifyDataSetChanged();
    }

    static class RiwayatIzinViewHolder extends RecyclerView.ViewHolder {
        TextView tvTanggalIzin, tvJenisIzin, tvKeteranganIzin, tvStatusIzin;

        public RiwayatIzinViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTanggalIzin = itemView.findViewById(R.id.tvTanggalIzin);
            tvJenisIzin = itemView.findViewById(R.id.tvJenisIzin);
            tvKeteranganIzin = itemView.findViewById(R.id.tvKeteranganIzin);
            tvStatusIzin = itemView.findViewById(R.id.tvStatusIzin);
        }
    }
}
