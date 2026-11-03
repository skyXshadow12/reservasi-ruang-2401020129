<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Reservasi Ruang</title>
</head>
<body>
    <h1>Edit Reservasi Ruang</h1>
    <a href="{{ route('reservasi.index') }}">Kembali</a>
    <br><br>

    @if ($errors->any())
        <ul style="color: red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('reservasi.update', $reservasi['id']) }}" method="POST">
        @csrf
        @method('PUT')
        <p>Kode Reservasi:<br><input type="text" name="kode_reservasi" value="{{ old('kode_reservasi', $reservasi['kode_reservasi']) }}" required></p>
        <p>Nama Peminjam:<br><input type="text" name="nama_peminjam" value="{{ old('nama_peminjam', $reservasi['nama_peminjam']) }}" required></p>
        <p>Email:<br><input type="email" name="email" value="{{ old('email', $reservasi['email']) }}" required></p>
        <p>Durasi (Jam, 1-8):<br><input type="number" name="durasi_jam" value="{{ old('durasi_jam', $reservasi['durasi_jam']) }}" min="1" max="8" required></p>
        <p>Ruang:<br>
            <select name="ruang_id" required>
                @foreach($ruangs as $r)
                    <option value="{{ $r['id'] }}" @selected(old('ruang_id', $reservasi['ruang_id']) == $r['id'])>{{ $r['nama_ruang'] }}</option>
                @endforeach
            </select>
        </p>
        <button type="submit">Perbarui Data</button>
    </form>
</body>
</html>