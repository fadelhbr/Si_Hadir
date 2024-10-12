package com.example.hadir.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.hadir.R;
import com.example.hadir.model.Pengumuman;

import java.util.List;

public class PengumumanAdapter extends RecyclerView.Adapter<PengumumanAdapter.PengumumanViewHolder> {

    private List<Pengumuman> pengumumanList;
    private OnItemClickListener listener;

    public void updateData(List<Pengumuman> pengumumanList) {
    }

    public interface OnItemClickListener {
        void onItemClick(Pengumuman pengumuman);
    }

    public PengumumanAdapter(List<Pengumuman> pengumumanList) {
        this.pengumumanList = pengumumanList;
        this.listener = listener;
    }

    @NonNull
    @Override
    public PengumumanViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_pengumuman, parent, false);
        return new PengumumanViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull PengumumanViewHolder holder, int position) {
        holder.bind(pengumumanList.get(position));
    }

    @Override
    public int getItemCount() {
        return pengumumanList.size();
    }

    public class PengumumanViewHolder extends RecyclerView.ViewHolder {
        private TextView tvJudulPengumuman;
        private TextView tvIsiPengumuman;
        private TextView tvTanggalPengumuman;

        public PengumumanViewHolder(@NonNull View itemView) {
            super(itemView);
            tvJudulPengumuman = itemView.findViewById(R.id.tv_judul_pengumuman);
            tvIsiPengumuman = itemView.findViewById(R.id.tv_isi_pengumuman);
            tvTanggalPengumuman = itemView.findViewById(R.id.tv_tanggal_pengumuman);
        }

        public void bind(final Pengumuman pengumuman) {
            tvJudulPengumuman.setText(pengumuman.getJudul());
            tvIsiPengumuman.setText(pengumuman.getIsi());
            tvTanggalPengumuman.setText(pengumuman.getTanggal());

            itemView.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View v) {
                    listener.onItemClick(pengumuman);
                }
            });
        }
    }
}