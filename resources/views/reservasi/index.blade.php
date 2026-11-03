<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Reservasi Ruang - Roni Pati</title>
</head>
<body>
    <h1>Sistem Reservasi Ruang (Paket A)</h1>
    <p>Nama: Roni Pati Nasrani Tampubolon | NIM: 2401020129</p>

    @if(session('berhasil'))
        <p style="color: green;">{{ session('berhasil') }}</p>
    @endif
    @if(session('gagal'))
        <p style="color: red;">{{ session('gagal') }}</p>
    @endif

    @if ($errors->any())
        <ul style="color: red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <hr>

    <!-- FORM PENCARIAN & FILTER -->
    <form method="GET" action="{{ route('reservasi.index') }}">
        <p>
            Pencarian: <input type="text" name="search" value="{{ $keyword ?? '' }}" placeholder="Cari Kode / Nama / Email">
            Filter Ruang: 
            <select name="ruang_id">
                <option value="">-- Semua Ruang --</option>
                @foreach($ruangs as $r)
                    <option value="{{ $r['id'] }}" @selected(($ruangFilter ?? '') == $r['id'])>{{ $r['nama_ruang'] }}</option>
                @endforeach
            </select>
            <button type="submit">Cari & Filter</button>
            <a href="{{ route('reservasi.index') }}">Reset</a>
        </p>
    </form>

    <hr>

    <!-- FORM TAMBAH (CREATE) -->
    <h2>Tambah Reservasi Baru</h2>
    <form action="{{ route('reservasi.store') }}" method="POST">
        @csrf
        <p>
            Kode Reservasi (10 Karakter):<br>
            <!-- Tambahan placeholder sebagai bayangan panduan ngetik -->
            <input type="text" name="kode_reservasi" value="{{ old('kode_reservasi') }}" placeholder="Contoh: RS00000004" maxlength="10" required>
        </p>
        <p>
            Nama Peminjam:<br>
            <input type="text" name="nama_peminjam" value="{{ old('nama_peminjam') }}" placeholder="Masukkan nama lengkap" required>
        </p>
        <p>
            Email:<br>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="contoh@example.com" required>
        </p>
        <p>
            Durasi (Jam, 1-8):<br>
            <input type="number" name="durasi_jam" value="{{ old('durasi_jam') }}" min="1" max="8" placeholder="1 s.d 8" required>
        </p>
        <p>
            Ruang:<br>
            <select name="ruang_id" required>
                <option value="">-- Pilih Ruang --</option>
                @foreach($ruangs as $r)
                    <option value="{{ $r['id'] }}" @selected(old('ruang_id') == $r['id'])>{{ $r['nama_ruang'] }}</option>
                @endforeach
            </select>
        </p>
        <button type="submit">Simpan Reservasi</button>
    </form>

    <hr>

    <!-- TABEL DATA (READ) -->
    <h2>Daftar Reservasi Ruang</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Kode</th>
                <th>Nama Peminjam</th>
                <th>Email</th>
                <th>Durasi (Jam)</th>
                <th>Status Durasi</th>
                <th>Ruang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservasis as $item)
            <tr>
                <td>{{ $item['id'] }}</td>
                <td>{{ $item['kode_reservasi'] }}</td>
                <td>{{ $item['nama_peminjam'] }}</td>
                <td>{{ $item['email'] }}</td>
                <td>{{ $item['durasi_jam'] }}</td>
                <td>
                    <!-- Memanggil fungsi statusDurasi dari Controller -->
                    @php
                        $status = app('App\Http\Controllers\ReservasiController')->statusDurasi($item['durasi_jam']);
                    @endphp
                    <b>{{ $status }}</b>
                </td>
                <td>{{ $item['nama_ruang'] }}</td>
                <td>
                    <a href="{{ route('reservasi.edit', $item['id']) }}">Edit</a> | 
                    <form action="{{ route('reservasi.destroy', $item['id']) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8">Data reservasi tidak ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
