SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_lengkap` varchar(255) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `asal_sekolah` varchar(255) DEFAULT NULL,
  `jurusan_sekolah` varchar(100) DEFAULT NULL,
  `tahun_lulus` year(4) DEFAULT NULL,
  `pilihan_prodi_1` varchar(100) DEFAULT NULL,
  `pilihan_prodi_2` varchar(100) DEFAULT NULL,
  `jalur_seleksi` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `ijazah` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending','verified','accepted','rejected') DEFAULT 'draft',
  `catatan_admin` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `applicants`
--

INSERT INTO `applicants` (`id`, `user_id`, `nama_lengkap`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `kota`, `provinsi`, `kode_pos`, `no_telepon`, `asal_sekolah`, `jurusan_sekolah`, `tahun_lulus`, `pilihan_prodi_1`, `pilihan_prodi_2`, `jalur_seleksi`, `foto`, `ijazah`, `status`, `catatan_admin`, `updated_at`) VALUES
(8, 9, 'haha2', 'ss', '2026-04-14', 'L', 'dd', 'ff', 'jabar', '13231', '75634523', 'smk haha', 'IPA', '2024', 'Sistem dan Teknologi Informasi', 'Sistem dan Teknologi Informasi', 'PMB Jalur Prestasi 2025', '9_foto_1776746852.png', '9_ijazah_1776746855.png', 'accepted', '', '2026-04-21 04:48:16'),
(9, 10, 'haha', 'bandung', '2026-04-18', 'L', 'hjkl;', 'bogor', '4gt3ed2', '13231', '75634523', 'ff', 'Teknik', '2022', 'Perencanaan Wilayah dan Kota', 'Perencanaan Wilayah dan Kota', 'PMB Jalur Beasiswa 2025', NULL, NULL, 'pending', NULL, '2026-04-21 07:03:24'),
(10, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, '2026-04-21 07:27:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nomor_pendaftaran` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('applicant','admin') DEFAULT 'applicant',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nomor_pendaftaran`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'ADMIN001', 'admin@itb.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-04-20 11:18:07'),
(9, 'ITB202626603', 'haha@gmail.com', '$2y$10$resYdHvAgoDvC/ytBgr4OegqzLmIKZ0VxZIHhojJxTeOHNb49GDJG', 'applicant', '2026-04-21 04:41:42'),
(10, 'ITB202679933', 'lala@gmail.com', '$2y$10$VbUDSV/h1PplV0skezxNP.NvftqK6jyqL3yfManTXTGjwgitpGo76', 'applicant', '2026-04-21 07:01:08'),
(11, 'ITB202612603', 'haha2@gmail.com', '$2y$10$I7okr35nCTxe1TZyW3iVxOatOZV2Z0rMxCH62lNkbchxxLKKosF1m', 'applicant', '2026-04-21 07:27:04');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_pendaftaran` (`nomor_pendaftaran`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
