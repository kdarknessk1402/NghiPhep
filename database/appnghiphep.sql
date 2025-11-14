-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 14, 2025 lúc 09:55 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `appnghiphep`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cauhinhemail`
--

CREATE TABLE `cauhinhemail` (
  `MaCauHinh` int(11) NOT NULL,
  `SmtpHost` varchar(100) NOT NULL,
  `SmtpPort` int(11) NOT NULL,
  `SmtpUsername` varchar(100) NOT NULL,
  `SmtpPassword` varchar(255) NOT NULL,
  `EmailNguoiGui` varchar(100) NOT NULL,
  `TenNguoiGui` varchar(100) NOT NULL,
  `EmailNhan` varchar(100) NOT NULL,
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cauhinhemail`
--

INSERT INTO `cauhinhemail` (`MaCauHinh`, `SmtpHost`, `SmtpPort`, `SmtpUsername`, `SmtpPassword`, `EmailNguoiGui`, `TenNguoiGui`, `EmailNhan`, `NgayCapNhat`) VALUES
(1, 'smtp.gmail.com', 587, 'thbao.thuduc@gmail.com', 'gzgiilqoihmefzve', 'thbao.thuduc@gmail.com', 'Hệ thống nghỉ phép', 'admin@school.edu.vn', '2025-11-11 07:12:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Khoa Công nghệ thông tin'),
(2, 'Phòng Hành chính'),
(3, 'Khoa Điện tử'),
(4, 'Khoa Ngoại ngữ');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donnghiphep`
--

CREATE TABLE `donnghiphep` (
  `MaDon` varchar(20) NOT NULL,
  `MaNguoiDung` varchar(50) NOT NULL,
  `NguoiTao` varchar(50) DEFAULT NULL,
  `LoaiPhep` varchar(50) NOT NULL,
  `LoaiDon` enum('Phep_thuong','Nghi_bu') DEFAULT 'Phep_thuong',
  `MaNghiBu` int(11) DEFAULT NULL,
  `NgayBatDauNghi` date NOT NULL,
  `NghiNuaNgayBatDau` enum('Khong','Sang','Chieu') DEFAULT 'Khong',
  `NgayKetThucNghi` date NOT NULL,
  `NghiNuaNgayKetThuc` enum('Khong','Sang','Chieu') DEFAULT 'Khong',
  `SoNgayNghi` decimal(5,1) NOT NULL,
  `LyDo` varchar(100) NOT NULL,
  `TrangThai` enum('WAITING','ACCEPT','DENY') DEFAULT 'WAITING',
  `GhiChuAdmin` varchar(100) DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `donnghiphep`
--

INSERT INTO `donnghiphep` (`MaDon`, `MaNguoiDung`, `NguoiTao`, `LoaiPhep`, `LoaiDon`, `MaNghiBu`, `NgayBatDauNghi`, `NghiNuaNgayBatDau`, `NgayKetThucNghi`, `NghiNuaNgayKetThuc`, `SoNgayNghi`, `LyDo`, `TrangThai`, `GhiChuAdmin`, `NgayTao`, `NgayCapNhat`) VALUES
('DN202511111853', 'USER001', 'USER001', 'Phép ốm', 'Phep_thuong', NULL, '2025-11-17', 'Khong', '2025-11-19', 'Khong', 3.0, 'acsaccscs', 'DENY', 'asdada', '2025-11-11 07:15:38', '2025-11-12 00:33:30'),
('DN202511111863', 'USER001', 'USER001', 'Phép năm', 'Phep_thuong', NULL, '2025-11-11', 'Khong', '2025-11-12', 'Khong', 2.0, 'asda', 'ACCEPT', 'duyet', '2025-11-11 06:54:47', '2025-11-12 00:33:30'),
('DN202511112426', 'USER001', 'USER001', 'Phép năm', 'Phep_thuong', NULL, '2025-11-21', 'Khong', '2025-11-22', 'Khong', 2.0, 'thich thi nghi', 'ACCEPT', 'asdad', '2025-11-11 07:19:41', '2025-11-12 00:33:30'),
('DN202511115498', 'USER001', 'USER001', 'Phép năm', 'Phep_thuong', NULL, '2025-11-14', 'Khong', '2025-11-18', 'Khong', 5.0, 'Abc', 'DENY', 'asda', '2025-11-11 07:02:34', '2025-11-12 00:33:30'),
('DN202511123576', 'USER002', 'USER002', 'Phép năm', 'Phep_thuong', NULL, '2025-11-12', 'Sang', '2025-11-13', 'Khong', 1.5, 'abc', 'WAITING', NULL, '2025-11-12 00:37:07', '2025-11-12 00:37:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichdaybu`
--

CREATE TABLE `lichdaybu` (
  `MaLich` int(11) NOT NULL,
  `MaDon` varchar(20) NOT NULL,
  `MaNguoiDung` varchar(50) NOT NULL,
  `NgayDayBanDau` date NOT NULL,
  `BuoiDayBanDau` varchar(10) NOT NULL,
  `LopHocBanDau` varchar(10) NOT NULL,
  `MonHocBanDau` varchar(100) NOT NULL,
  `NgayDayBu` date NOT NULL,
  `BuoiDayBu` varchar(10) NOT NULL,
  `LopHocBu` varchar(10) NOT NULL,
  `MonHocBu` varchar(100) NOT NULL,
  `GhiChu` varchar(255) DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsuemail`
--

CREATE TABLE `lichsuemail` (
  `MaLichSu` int(11) NOT NULL,
  `MaDon` varchar(20) NOT NULL,
  `EmailNhan` varchar(100) NOT NULL,
  `TieuDeEmail` varchar(255) NOT NULL,
  `TrangThai` enum('Thanh_cong','That_bai') NOT NULL,
  `ThongBaoLoi` varchar(255) DEFAULT NULL,
  `ThoiGianGui` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lichsuemail`
--

INSERT INTO `lichsuemail` (`MaLichSu`, `MaDon`, `EmailNhan`, `TieuDeEmail`, `TrangThai`, `ThongBaoLoi`, `ThoiGianGui`) VALUES
(1, 'DN202511111863', 'Array', '[APPNGHIPHEP] Đơn nghỉ phép mới - DN202511111863', 'That_bai', 'Lỗi gửi email: SMTP Error: Could not authenticate.', '2025-11-11 06:54:50'),
(2, 'DN202511111863', 'user1@school.edu.vn', '[APPNGHIPHEP] Đơn nghỉ phép đã được duyệt - DN202511111863', 'That_bai', 'Lỗi gửi email: SMTP Error: Could not authenticate.', '2025-11-11 06:55:27'),
(3, 'DN202511115498', 'thbao.thuduc@gmail.com', '[ĐƠN NGHỈ PHÉP] Trần Thị User - DN202511115498', 'That_bai', 'SMTP Error: Could not authenticate.', '2025-11-11 07:02:37'),
(4, 'DN202511115498', 'thbao.thuduc@gmail.com', '[TỪ CHỐI] Đơn nghỉ phép DN202511115498 bị từ chối', 'Thanh_cong', NULL, '2025-11-11 07:15:16'),
(5, 'DN202511111853', 'kdarknessk1402@gmail.com', '[ĐƠN NGHỈ PHÉP] Trần Thị User - DN202511111853', 'Thanh_cong', NULL, '2025-11-11 07:15:42'),
(6, 'DN202511112426', 'kdarknessk1402@gmail.com', '[ĐƠN NGHỈ PHÉP] Trần Thị User - DN202511112426', 'Thanh_cong', NULL, '2025-11-11 07:19:46'),
(7, 'DN202511112426', 'thbao.thuduc@gmail.com', '[PHÊ DUYỆT] Đơn nghỉ phép DN202511112426 đã được duyệt', 'Thanh_cong', NULL, '2025-11-11 07:20:31'),
(8, 'DN202511111853', 'thbao.thuduc@gmail.com', '[TỪ CHỐI] Đơn nghỉ phép DN202511111853 bị từ chối', 'Thanh_cong', NULL, '2025-11-11 07:20:41'),
(9, 'DN202511123576', 'manager2@school.edu.vn, kdarknessk1402@gmail.com', '[ĐƠN NGHỈ PHÉP] Nguyễn Thị B - DN202511123576', 'Thanh_cong', NULL, '2025-11-12 00:37:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsuphepton`
--

CREATE TABLE `lichsuphepton` (
  `MaLichSu` int(11) NOT NULL,
  `MaNguoiDung` varchar(50) NOT NULL,
  `Nam` int(11) NOT NULL,
  `SoNgayPhepDuocCap` decimal(5,1) NOT NULL,
  `SoNgayPhepDaSuDung` decimal(5,1) DEFAULT 0.0,
  `SoNgayPhepDu` decimal(5,1) DEFAULT 0.0,
  `SoNgayPhepTonChuyenSangNamSau` decimal(5,1) DEFAULT 0.0,
  `SoNgayPhepTonDaSuDungNamSau` decimal(5,1) DEFAULT 0.0,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nghibu`
--

CREATE TABLE `nghibu` (
  `MaNghiBu` int(11) NOT NULL,
  `MaNguoiDung` varchar(50) NOT NULL,
  `LoaiNghiBu` enum('Nghi_truoc_lam_sau','Lam_truoc_nghi_sau') NOT NULL,
  `NgayNghiBu` date NOT NULL,
  `SoNgayNghi` decimal(5,1) NOT NULL DEFAULT 1.0,
  `NgayLamBu` date DEFAULT NULL,
  `SoNgayLam` decimal(5,1) DEFAULT 0.0,
  `TrangThai` enum('Cho_lam_bu','Da_lam_bu','Qua_han') DEFAULT 'Cho_lam_bu',
  `LyDo` text DEFAULT NULL,
  `GhiChu` varchar(255) DEFAULT NULL,
  `NguoiDuyet` varchar(50) DEFAULT NULL,
  `TrangThaiDuyet` enum('WAITING','ACCEPT','DENY') DEFAULT 'WAITING',
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nghibu`
--

INSERT INTO `nghibu` (`MaNghiBu`, `MaNguoiDung`, `LoaiNghiBu`, `NgayNghiBu`, `SoNgayNghi`, `NgayLamBu`, `SoNgayLam`, `TrangThai`, `LyDo`, `GhiChu`, `NguoiDuyet`, `TrangThaiDuyet`, `NgayTao`, `NgayCapNhat`) VALUES
(1, 'USER001', 'Lam_truoc_nghi_sau', '2025-12-02', 1.0, '2025-11-30', 0.0, 'Cho_lam_bu', 'Làm thêm ngày cuối tuần để hoàn thành dự án', NULL, NULL, 'ACCEPT', '2025-11-12 06:13:33', '2025-11-12 06:13:33'),
(2, 'USER001', 'Nghi_truoc_lam_sau', '2025-12-10', 1.0, '2025-12-14', 0.0, 'Cho_lam_bu', 'Có việc gia đình đột xuất, sẽ làm bù cuối tuần', NULL, NULL, 'WAITING', '2025-11-12 06:13:33', '2025-11-12 06:13:33');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoidung`
--

CREATE TABLE `nguoidung` (
  `MaNguoiDung` varchar(50) NOT NULL,
  `TenDangNhap` varchar(50) NOT NULL,
  `MatKhau` varchar(255) NOT NULL,
  `HoTen` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `GioiTinh` enum('Nam','Nu') DEFAULT 'Nam',
  `ViTri` varchar(100) NOT NULL,
  `KhoaPhong` varchar(100) DEFAULT NULL,
  `MaVaiTro` int(11) NOT NULL DEFAULT 2,
  `NamBatDauLamViec` datetime DEFAULT NULL,
  `SoNgayPhepNam` int(11) DEFAULT 12,
  `SoNgayPhepDaDung` decimal(5,1) DEFAULT 0.0,
  `SoNgayPhepTonNamTruoc` decimal(5,1) DEFAULT 0.0 COMMENT 'Số ngày phép tồn từ năm trước (chỉ dùng được trong Q1)',
  `NamPhepTon` int(11) DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoidung`
--

INSERT INTO `nguoidung` (`MaNguoiDung`, `TenDangNhap`, `MatKhau`, `HoTen`, `Email`, `GioiTinh`, `ViTri`, `KhoaPhong`, `MaVaiTro`, `NamBatDauLamViec`, `SoNgayPhepNam`, `SoNgayPhepDaDung`, `SoNgayPhepTonNamTruoc`, `NamPhepTon`, `NgayTao`) VALUES
('ADMIN001', 'admin', '$2y$10$CbSC6D2gAraB73C.aI6oxuQ0FIbORrOsAnMqL.eqXWBPsC2nTsPtW', 'Nguyễn Văn Admin', 'kdarknessk1402@gmail.com', 'Nam', 'Quản trị viên', 'Phòng Đào tạo', 1, '2020-01-01 00:00:00', 12, 0.0, 0.0, NULL, '2025-11-10 09:16:36'),
('MGR001', 'manager', '$2y$10$BT6yImKCAbgJ5swoRChxweMAy6Vk4vWY4N9354qfCJsHcIJdiVyJe', 'Lê Văn Quản lý', 'kdarknessk1402@gmail.com', 'Nam', 'Trưởng khoa', 'Khoa Công nghệ', 3, '2019-09-01 00:00:00', 12, 0.0, 0.0, NULL, '2025-11-10 09:16:36'),
('MGR002', 'manager2', '$2y$10$veApIBNhO3tnvxPYhlcTyedMReOcOKNy.dpW1YGpCrUhggC4Xd8.y', 'Trần Văn Manager 2', 'manager2@school.edu.vn', 'Nam', 'Trưởng khoa', 'Khoa Kinh tế', 3, '2020-01-01 00:00:00', 12, 0.0, 0.0, NULL, '2025-11-12 00:33:30'),
('USER001', 'user1', '$2y$10$weY6fDQ0faZzBkEY32lK3u3IIKTEASoGccyvMMYkJItgxTqD7XdQm', 'Trần Thị User', 'thbao.thuduc@gmail.com', 'Nu', 'Giảng viên', 'Khoa Công nghệ', 2, '2021-06-15 00:00:00', 12, 8.0, 4.0, 2024, '2025-11-10 09:16:36'),
('USER002', 'user2', '$2y$10$dpgK3djqPuUUrKb5kKeUX.Yxmt2Em.M..YFZdfw4lLsnQVUkFztUe', 'Nguyễn Thị B', 'user2@school.edu.vn', 'Nu', '', 'Khoa Công nghệ thông tin', 2, '2022-01-01 00:00:00', 12, 0.0, 0.0, NULL, '2025-11-12 00:33:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Giảng Viên'),
(2, 'Nhân viên hành chính'),
(3, 'Trưởng khoa/phòng'),
(4, 'Phó hiệu trưởng Quản lý'),
(5, 'Trưởng phòng hành chính');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro`
--

CREATE TABLE `vaitro` (
  `MaVaiTro` int(11) NOT NULL,
  `TenVaiTro` varchar(50) NOT NULL,
  `MoTa` varchar(255) DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vaitro`
--

INSERT INTO `vaitro` (`MaVaiTro`, `TenVaiTro`, `MoTa`, `NgayTao`) VALUES
(1, 'ADMIN', 'Quản trị viên - Toàn quyền hệ thống', '2025-11-10 09:16:36'),
(2, 'USER', 'Người dùng - Nhân viên thông thường', '2025-11-10 09:16:36'),
(3, 'MANAGER', 'Quản lý - Duyệt đơn nghỉ phép', '2025-11-10 09:16:36');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_thongkephep`
-- (See below for the actual view)
--
CREATE TABLE `v_thongkephep` (
`MaNguoiDung` varchar(50)
,`HoTen` varchar(100)
,`KhoaPhong` varchar(100)
,`SoNgayPhepNam` int(11)
,`SoNgayPhepDaDung` decimal(5,1)
,`SoNgayPhepTonNamTruoc` decimal(5,1)
,`NamPhepTon` int(11)
,`PhepConLaiNamNay` decimal(12,1)
,`PhepTonConDungDuoc` decimal(5,1)
,`TongPhepCoTheDung` decimal(13,1)
,`SoLanChoLamBu` bigint(21)
,`SoNgayChoLamBu` decimal(27,1)
);

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_thongkephep`
--
DROP TABLE IF EXISTS `v_thongkephep`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_thongkephep`  AS SELECT `n`.`MaNguoiDung` AS `MaNguoiDung`, `n`.`HoTen` AS `HoTen`, `n`.`KhoaPhong` AS `KhoaPhong`, `n`.`SoNgayPhepNam` AS `SoNgayPhepNam`, `n`.`SoNgayPhepDaDung` AS `SoNgayPhepDaDung`, `n`.`SoNgayPhepTonNamTruoc` AS `SoNgayPhepTonNamTruoc`, `n`.`NamPhepTon` AS `NamPhepTon`, `n`.`SoNgayPhepNam`- `n`.`SoNgayPhepDaDung` AS `PhepConLaiNamNay`, CASE WHEN month(curdate()) <= 3 AND `n`.`NamPhepTon` = year(curdate()) - 1 THEN `n`.`SoNgayPhepTonNamTruoc` ELSE 0 END AS `PhepTonConDungDuoc`, `n`.`SoNgayPhepNam`- `n`.`SoNgayPhepDaDung` + CASE WHEN month(curdate()) <= 3 AND `n`.`NamPhepTon` = year(curdate()) - 1 THEN `n`.`SoNgayPhepTonNamTruoc` ELSE 0 END AS `TongPhepCoTheDung`, (select count(0) from `nghibu` where `nghibu`.`MaNguoiDung` = `n`.`MaNguoiDung` and `nghibu`.`TrangThai` = 'Cho_lam_bu') AS `SoLanChoLamBu`, (select sum(`nghibu`.`SoNgayNghi`) from `nghibu` where `nghibu`.`MaNguoiDung` = `n`.`MaNguoiDung` and `nghibu`.`TrangThai` = 'Cho_lam_bu') AS `SoNgayChoLamBu` FROM (`nguoidung` `n` join `vaitro` `v` on(`n`.`MaVaiTro` = `v`.`MaVaiTro`)) WHERE `v`.`TenVaiTro` = 'USER' ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cauhinhemail`
--
ALTER TABLE `cauhinhemail`
  ADD PRIMARY KEY (`MaCauHinh`);

--
-- Chỉ mục cho bảng `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `donnghiphep`
--
ALTER TABLE `donnghiphep`
  ADD PRIMARY KEY (`MaDon`),
  ADD KEY `MaNguoiDung` (`MaNguoiDung`),
  ADD KEY `NguoiTao` (`NguoiTao`),
  ADD KEY `MaNghiBu` (`MaNghiBu`);

--
-- Chỉ mục cho bảng `lichdaybu`
--
ALTER TABLE `lichdaybu`
  ADD PRIMARY KEY (`MaLich`),
  ADD KEY `MaDon` (`MaDon`),
  ADD KEY `MaNguoiDung` (`MaNguoiDung`);

--
-- Chỉ mục cho bảng `lichsuemail`
--
ALTER TABLE `lichsuemail`
  ADD PRIMARY KEY (`MaLichSu`),
  ADD KEY `MaDon` (`MaDon`);

--
-- Chỉ mục cho bảng `lichsuphepton`
--
ALTER TABLE `lichsuphepton`
  ADD PRIMARY KEY (`MaLichSu`),
  ADD UNIQUE KEY `unique_user_year` (`MaNguoiDung`,`Nam`);

--
-- Chỉ mục cho bảng `nghibu`
--
ALTER TABLE `nghibu`
  ADD PRIMARY KEY (`MaNghiBu`),
  ADD KEY `NguoiDuyet` (`NguoiDuyet`),
  ADD KEY `idx_nguoi_dung` (`MaNguoiDung`),
  ADD KEY `idx_trang_thai` (`TrangThai`),
  ADD KEY `idx_ngay_nghi_bu` (`NgayNghiBu`),
  ADD KEY `idx_ngay_lam_bu` (`NgayLamBu`);

--
-- Chỉ mục cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`MaNguoiDung`),
  ADD UNIQUE KEY `TenDangNhap` (`TenDangNhap`),
  ADD KEY `MaVaiTro` (`MaVaiTro`);

--
-- Chỉ mục cho bảng `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  ADD PRIMARY KEY (`MaVaiTro`),
  ADD UNIQUE KEY `TenVaiTro` (`TenVaiTro`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cauhinhemail`
--
ALTER TABLE `cauhinhemail`
  MODIFY `MaCauHinh` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `lichdaybu`
--
ALTER TABLE `lichdaybu`
  MODIFY `MaLich` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lichsuemail`
--
ALTER TABLE `lichsuemail`
  MODIFY `MaLichSu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `lichsuphepton`
--
ALTER TABLE `lichsuphepton`
  MODIFY `MaLichSu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nghibu`
--
ALTER TABLE `nghibu`
  MODIFY `MaNghiBu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  MODIFY `MaVaiTro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `donnghiphep`
--
ALTER TABLE `donnghiphep`
  ADD CONSTRAINT `donnghiphep_ibfk_1` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE,
  ADD CONSTRAINT `donnghiphep_ibfk_2` FOREIGN KEY (`NguoiTao`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE SET NULL,
  ADD CONSTRAINT `donnghiphep_ibfk_3` FOREIGN KEY (`MaNghiBu`) REFERENCES `nghibu` (`MaNghiBu`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `lichdaybu`
--
ALTER TABLE `lichdaybu`
  ADD CONSTRAINT `lichdaybu_ibfk_1` FOREIGN KEY (`MaDon`) REFERENCES `donnghiphep` (`MaDon`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichdaybu_ibfk_2` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lichsuemail`
--
ALTER TABLE `lichsuemail`
  ADD CONSTRAINT `lichsuemail_ibfk_1` FOREIGN KEY (`MaDon`) REFERENCES `donnghiphep` (`MaDon`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lichsuphepton`
--
ALTER TABLE `lichsuphepton`
  ADD CONSTRAINT `lichsuphepton_ibfk_1` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `nghibu`
--
ALTER TABLE `nghibu`
  ADD CONSTRAINT `nghibu_ibfk_1` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE,
  ADD CONSTRAINT `nghibu_ibfk_2` FOREIGN KEY (`NguoiDuyet`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD CONSTRAINT `nguoidung_ibfk_1` FOREIGN KEY (`MaVaiTro`) REFERENCES `vaitro` (`MaVaiTro`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
