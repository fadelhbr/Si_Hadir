package com.teamone.sihadir.adapter;

import android.content.res.Resources;
import android.graphics.drawable.GradientDrawable;
import android.util.TypedValue;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.teamone.sihadir.R;
import com.teamone.sihadir.model.Riwayat;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Locale;

public class RiwayatAdapter extends RecyclerView.Adapter<RiwayatAdapter.ViewHolder> {

    private List<Riwayat> riwayatList;
    private SimpleDateFormat inputDateFormat = new SimpleDateFormat("yyyy-MM-dd", Locale.getDefault());
    private SimpleDateFormat outputDateFormat = new SimpleDateFormat("dd-MM-yyyy", Locale.getDefault());
    private SimpleDateFormat inputTimeFormat = new SimpleDateFormat("HH:mm:ss", Locale.getDefault());
    private SimpleDateFormat outputTimeFormat = new SimpleDateFormat("HH:mm", Locale.getDefault());

    public RiwayatAdapter(List<Riwayat> riwayatList) {
        this.riwayatList = riwayatList;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_riwayat_kehadiran, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
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

        // Round corners for the last row
        if (position == riwayatList.size() - 1) {
            GradientDrawable drawable = new GradientDrawable();
            drawable.setColor(typedValue.data);
            drawable.setCornerRadii(new float[]{
                    0, 0,   // top-left radius
                    0, 0,   // top-right radius
                    25, 25, // bottom-right radius
                    25, 25  // bottom-left radius
            });
            holder.itemView.setBackground(drawable);
        }

        Riwayat riwayat = riwayatList.get(position);

        // Format data
        String formattedTanggal = formatTanggal(riwayat.getTanggal());
        String formattedJadwalShift = formatString(riwayat.getJadwalShift());
        String formattedWaktuMasuk = formatWaktu(riwayat.getWaktuMasuk());
        String formattedWaktuKeluar = formatWaktu(riwayat.getWaktuKeluar());
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

    // Format tanggal dengan format dd, MMMM yyyy
    private String formatTanggal(String input) {
        if (input == null || input.isEmpty()) {
            return "-";
        }
        try {
            Date date = inputDateFormat.parse(input);
            return outputDateFormat.format(date);
        } catch (ParseException e) {
            return input;
        }
    }

    // Format waktu tanpa detik
    private String formatWaktu(String input) {
        if (input == null || input.isEmpty()) {
            return "-";
        }
        try {
            Date time = inputTimeFormat.parse(input);
            return outputTimeFormat.format(time);
        } catch (ParseException e) {
            return input;
        }
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