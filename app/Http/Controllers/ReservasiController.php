<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservasiController extends Controller
{
    // Fungsi khusus ketentuan Paket A: statusDurasi()
    public function statusDurasi($durasi) {
        return $durasi >= 4 ? 'Panjang' : 'Singkat';
    }

    public function index(Request $request)
    {
        try {
            $pdo = DB::connection()->getPdo();
            
            $keyword = trim($request->input('search', ''));
            $ruangFilter = $request->input('ruang_id', '');

            // Query Read dengan JOIN
            $sql = "SELECT r.*, rn.nama_ruang 
                    FROM reservasi_ruang AS r
                    JOIN ruang AS rn ON r.ruang_id = rn.id WHERE 1=1";
            $params = [];

            // Fitur Pencarian (kode_reservasi, nama_peminjam, email)
            if (!empty($keyword)) {
                $sql .= " AND (r.kode_reservasi LIKE :keyword1 OR r.nama_peminjam LIKE :keyword2 OR r.email LIKE :keyword3)";
                $params['keyword1'] = "%{$keyword}%";
                $params['keyword2'] = "%{$keyword}%";
                $params['keyword3'] = "%{$keyword}%";
            }

            // Fitur Filter Berdasarkan Ruang
            if (!empty($ruangFilter)) {
                $sql .= " AND r.ruang_id = :ruang_id";
                $params['ruang_id'] = $ruangFilter;
            }

            $sql .= " ORDER BY r.id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $reservasis = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Ambil data ruang untuk pilihan select/filter
            $stmtRuang = $pdo->prepare("SELECT * FROM ruang ORDER BY nama_ruang");
            $stmtRuang->execute();
            $ruangs = $stmtRuang->fetchAll(\PDO::FETCH_ASSOC);

            return view('reservasi.index', compact('reservasis', 'ruangs', 'keyword', 'ruangFilter'));

        } catch (\Throwable $error) {
            report($error);
            return response('Terjadi kesalahan pada sistem basis data. Silakan coba beberapa saat lagi.', 500);
        }
    }

    public function store(Request $request)
    {
        // Sanitasi input teks
        $kode = trim(strip_tags($request->input('kode_reservasi')));
        $nama = trim(strip_tags($request->input('nama_peminjam')));
        $email = trim(strip_tags($request->input('email')));
        $durasi = $request->input('durasi_jam');
        $ruangId = $request->input('ruang_id');

        $request->merge([
            'kode_reservasi' => $kode,
            'nama_peminjam' => $nama,
            'email' => $email
        ]);

        // Validasi Server-Side ketat
        $request->validate([
            'kode_reservasi' => ['required', 'regex:/^[a-zA-Z0-9]{10}$/'],
            'nama_peminjam' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'durasi_jam' => ['required', 'integer', 'between:1,8'],
            'ruang_id' => ['required', 'integer'],
        ]);

        try {
            $pdo = DB::connection()->getPdo();

            // Cek duplikasi kode_reservasi atau email
            $check = $pdo->prepare("SELECT COUNT(*) FROM reservasi_ruang WHERE kode_reservasi = :kode OR email = :email");
            $check->execute(['kode' => $kode, 'email' => $email]);
            if ((int)$check->fetchColumn() > 0) {
                return back()->withInput()->with('gagal', 'Kode reservasi atau email sudah terdaftar (Duplikat).');
            }

            // Prepared statement INSERT
            $insert = $pdo->prepare("INSERT INTO reservasi_ruang (kode_reservasi, nama_peminjam, email, durasi_jam, ruang_id, created_at, updated_at) VALUES (:kode, :nama, :email, :durasi, :ruang_id, NOW(), NOW())");
            $insert->execute([
                'kode' => $kode,
                'nama' => $nama,
                'email' => $email,
                'durasi' => $durasi,
                'ruang_id' => $ruangId
            ]);

            return redirect()->route('reservasi.index')->with('berhasil', 'Data reservasi ruang berhasil ditambahkan.');

        } catch (\Throwable $error) {
            report($error);
            return back()->withInput()->with('gagal', 'Gagal menyimpan data ke database.');
        }
    }

    public function edit($id)
    {
        try {
            $pdo = DB::connection()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM reservasi_ruang WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $reservasi = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$reservasi) {
                return redirect()->route('reservasi.index')->with('gagal', 'Data tidak ditemukan.');
            }

            $stmtRuang = $pdo->prepare("SELECT * FROM ruang ORDER BY nama_ruang");
            $stmtRuang->execute();
            $ruangs = $stmtRuang->fetchAll(\PDO::FETCH_ASSOC);

            return view('reservasi.edit', compact('reservasi', 'ruangs'));
        } catch (\Throwable $error) {
            report($error);
            return response('Kesalahan sistem.', 500);
        }
    }

    public function update(Request $request, $id)
    {
        $kode = trim(strip_tags($request->input('kode_reservasi')));
        $nama = trim(strip_tags($request->input('nama_peminjam')));
        $email = trim(strip_tags($request->input('email')));
        $durasi = $request->input('durasi_jam');
        $ruangId = $request->input('ruang_id');

        $request->validate([
            'kode_reservasi' => ['required', 'regex:/^[a-zA-Z0-9]{10}$/'],
            'nama_peminjam' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'durasi_jam' => ['required', 'integer', 'between:1,8'],
            'ruang_id' => ['required', 'integer'],
        ]);

        try {
            $pdo = DB::connection()->getPdo();

            // Konflik Update: Cek duplikasi nilai unik milik data lain
            $check = $pdo->prepare("SELECT COUNT(*) FROM reservasi_ruang WHERE (kode_reservasi = :kode OR email = :email) AND id != :id");
            $check->execute(['kode' => $kode, 'email' => $email, 'id' => $id]);
            if ((int)$check->fetchColumn() > 0) {
                return back()->withInput()->with('gagal', 'Kode reservasi atau email sudah digunakan oleh data lain.');
            }

            $update = $pdo->prepare("UPDATE reservasi_ruang SET kode_reservasi = :kode, nama_peminjam = :nama, email = :email, durasi_jam = :durasi, ruang_id = :ruang_id, updated_at = NOW() WHERE id = :id");
            $update->execute([
                'kode' => $kode,
                'nama' => $nama,
                'email' => $email,
                'durasi' => $durasi,
                'ruang_id' => $ruangId,
                'id' => $id
            ]);

            return redirect()->route('reservasi.index')->with('berhasil', 'Data reservasi berhasil diperbarui.');

        } catch (\Throwable $error) {
            report($error);
            return back()->withInput()->with('gagal', 'Gagal memperbarui data.');
        }
    }

    public function destroy($id)
    {
        try {
            $pdo = DB::connection()->getPdo();
            $stmt = $pdo->prepare("DELETE FROM reservasi_ruang WHERE id = :id");
            $stmt->execute(['id' => $id]);

            return redirect()->route('reservasi.index')->with('berhasil', 'Data reservasi berhasil dihapus.');
        } catch (\Throwable $error) {
            report($error);
            return redirect()->route('reservasi.index')->with('gagal', 'Gagal menghapus data.');
        }
    }
}