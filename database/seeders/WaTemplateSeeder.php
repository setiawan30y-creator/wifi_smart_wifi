<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WaTemplate;

class WaTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'kirim_tagihan',
                'title' => 'Kirim Tagihan',
                'content' => "Halo {nama_pelanggan},\n\nTagihan internet bulan {bulan} sebesar Rp {nominal} telah diterbitkan dengan nomor tagihan {nomor_tagihan}.\n\nMohon segera melakukan pembayaran sebelum tanggal jatuh tempo. Terima kasih atas kerja samanya.",
                'is_system' => true,
            ],
            [
                'name' => 'bayar',
                'title' => 'Konfirmasi Pembayaran',
                'content' => "Halo {nama_pelanggan},\n\nPembayaran tagihan nomor {nomor_tagihan} sebesar Rp {nominal} telah kami terima dan dinyatakan Lunas.\n\nTerima kasih telah menggunakan layanan internet kami.",
                'is_system' => true,
            ],
            [
                'name' => 'bayar_lengkap',
                'title' => 'Konfirmasi Pembayaran Lengkap',
                'content' => "Halo {nama_pelanggan},\n\nPembayaran internet Anda telah kami terima.\n\nRincian pembayaran:\n{rincian_tagihan}\n\nTotal dibayar: {nominal}\nMetode bayar: {metode_bayar}\nTanggal bayar: {tanggal_bayar}\n\nSisa tagihan:\n{sisa_tagihan}\n\nTerima kasih.",
                'is_system' => false,
            ],
            [
                'name' => 'bayar_dengan_sisa_tagihan',
                'title' => 'Notifikasi Pembayaran + Sisa Tagihan',
                'content' => "*Notifikasi Pembayaran*\n\nHallo kak {nama_pelanggan},\nNo Tagihan : *{nomor_tagihan}*\nPeriode : *{periode_bayar}*\nsebesar : *{nominal_dibayar}*\n: {nominal_dibayar_terbilang}\ntgl : *{tanggal_bayar}*\nvia : *{metode_bayar}*\nsisa tagihan :\n{info_sisa_tagihan}\n\nTerima kasih telah menggunakan layanan internet kami.",
                'is_system' => false,
            ],
            [
                'name' => 'jaringan_down_odp',
                'title' => 'Jaringan Down ODP',
                'content' => "Pemberitahuan:\n\nPelanggan yang terhormat pada ODP {nama_odp}, saat ini sedang terjadi kendala teknis pada jalur distribusi Anda yang menyebabkan jaringan terputus sementara.\n\nTim teknisi kami sedang melakukan penanganan secepatnya. Mohon maaf atas ketidaknyamanannya.",
                'is_system' => true,
            ],
            [
                'name' => 'janji_bayar',
                'title' => 'Reminder Janji Bayar',
                'content' => "Halo kak {nama_pelanggan},\n\nKami ingatkan kembali janji pembayaran internet kakak pada tanggal *{tanggal_janji_bayar}*.\n\nNo Tagihan: *{nomor_tagihan}*\nPeriode: *{periode}*\nNominal: *{nominal}*\nCatatan: {catatan_janji_bayar}\n\nMohon melakukan pembayaran sesuai janji. Jika sudah membayar, abaikan pesan ini. Terima kasih.",
                'is_system' => false,
            ],
            [
                'name' => 'jaringan_up_odp',
                'title' => 'Jaringan Up ODP',
                'content' => "Pemberitahuan:\n\nJaringan pada ODP {nama_odp} telah kembali normal secara keseluruhan.\n\nSilakan lakukan restart router/ONT Anda jika koneksi belum tersambung secara otomatis. Terima kasih atas kesabaran Anda.",
                'is_system' => true,
            ],
            [
                'name' => 'jaringan_down_pusat',
                'title' => 'Jaringan Down Pusat',
                'content' => "Pemberitahuan Penting:\n\nSaat ini sedang terdeteksi gangguan massal pada jaringan pusat kami yang menyebabkan koneksi internet terputus sementara.\n\nTim teknisi kami sedang berupaya maksimal melakukan perbaikan secepatnya. Mohon maaf atas kendala yang terjadi.",
                'is_system' => true,
            ],
            [
                'name' => 'jaringan_up_pusat',
                'title' => 'Jaringan Up Pusat',
                'content' => "Kabar Baik:\n\nGangguan jaringan pusat telah selesai diperbaiki dan koneksi internet Anda sudah kembali normal secara keseluruhan.\n\nTerima kasih banyak atas kesabaran dan pengertian Anda selama proses perbaikan.",
                'is_system' => true,
            ],
        ];

        foreach ($templates as $tmpl) {
            if ($tmpl['name'] === 'bayar_dengan_sisa_tagihan') {
                WaTemplate::updateOrCreate(['name' => $tmpl['name']], $tmpl);
                continue;
            }

            WaTemplate::firstOrCreate(['name' => $tmpl['name']], $tmpl);
        }
    }
}
