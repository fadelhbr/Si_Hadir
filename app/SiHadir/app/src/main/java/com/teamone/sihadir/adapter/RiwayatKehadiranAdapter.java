package com.example.hadir.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.hadir.R;
import com.example.hadir.model.Kehadiran;
import java.text.SimpleDateFormat;
import java.util.List;
import java.util.Locale;

public class RiwayatKehadiranAdapter extends RecyclerView.Adapter<RiwayatKehadiranAdapter.ViewHolder> {

    private List<Kehadiran> kehadiranList;
    private SimpleDateFormat dateFormat = new SimpleDateFormat("dd/MM/yyyy", Locale.getDefault());

    public RiwayatKehadiranAdapter(List<Kehadiran> kehadiranList) {
        this.kehadiranList = kehadiranList;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_riwayat_kehadiran, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Kehadiran kehadiran = kehadiranList.get(position);
        holder.tvTanggal.setText(dateFormat.format(kehadiran.getTanggal()));
        holder.tvStatus.setText(kehadiran.getStatus());
        holder.tvJamMasuk.setText(kehadiran.getJamMasuk());
        holder.tvJamKeluar.setText(kehadiran.getJamKeluar());
    }

    @Override
    public int getItemCount() {
        return kehadiranList.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTanggal, tvStatus, tvJamMasuk, tvJamKeluar;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTanggal = itemView.findViewById(R.id.tv_tanggal);
            tvStatus = itemView.findViewById(R.id.tv_status);
            tvJamMasuk = itemView.findViewById(R.id.tv_jam_masuk);
            tvJamKeluar = itemView.findViewById(R.id.tv_jam_keluar);
        }
    }
}