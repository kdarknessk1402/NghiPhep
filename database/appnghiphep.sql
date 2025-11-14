-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 11, 2025 lúc 08:17 AM
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
-- Cấu trúc bảng cho bảng `donnghiphep`
--

CREATE TABLE `donnghiphep` (
  `MaDon` varchar(20) NOT NULL,
  `MaNguoiDung` varchar(50) NOT NULL,
  `LoaiPhep` varchar(50) NOT NULL,
  `NgayBatDauNghi` date NOT NULL,
  `NgayKetThucNghi` date NOT NULL,
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

INSERT INTO `donnghiphep` (`MaDon`, `MaNguoiDung`, `LoaiPhep`, `NgayBatDauNghi`, `NgayKetThucNghi`, `SoNgayNghi`, `LyDo`, `TrangThai`, `GhiChuAdmin`, `NgayTao`, `NgayCapNhat`) VALUES
('DN202511111853', 'USER001', 'Phép ốm', '2025-11-17', '2025-11-19', 3.0, 'acsaccscs', 'WAITING', NULL, '2025-11-11 07:15:38', '2025-11-11 07:15:38'),
('DN202511111863', 'USER001', 'Phép năm', '2025-11-11', '2025-11-12', 2.0, 'asda', 'ACCEPT', 'duyet', '2025-11-11 06:54:47', '2025-11-11 06:55:24'),
('DN202511115498', 'USER001', 'Phép năm', '2025-11-14', '2025-11-18', 5.0, 'Abc', 'DENY', 'asda', '2025-11-11 07:02:34', '2025-11-11 07:15:12');

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
(5, 'DN202511111853', 'kdarknessk1402@gmail.com', '[ĐƠN NGHỈ PHÉP] Trần Thị User - DN202511111853', 'Thanh_cong', NULL, '2025-11-11 07:15:42');

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
  `ViTri` varchar(100) NOT NULL,
  `KhoaPhong` varchar(100) DEFAULT NULL,
  `MaVaiTro` int(11) NOT NULL DEFAULT 2,
  `NamBatDauLamViec` datetime DEFAULT NULL,
  `SoNgayPhepNam` int(11) DEFAULT 12,
  `SoNgayPhepDaDung` decimal(5,1) DEFAULT 0.0,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoidung`
--

INSERT INTO `nguoidung` (`MaNguoiDung`, `TenDangNhap`, `MatKhau`, `HoTen`, `Email`, `ViTri`, `KhoaPhong`, `MaVaiTro`, `NamBatDauLamViec`, `SoNgayPhepNam`, `SoNgayPhepDaDung`, `NgayTao`) VALUES
('ADMIN001', 'admin', '$2y$10$CbSC6D2gAraB73C.aI6oxuQ0FIbORrOsAnMqL.eqXWBPsC2nTsPtW', 'Nguyễn Văn Admin', 'kdarknessk1402@gmail.com', 'Quản trị viên', 'Phòng Đào tạo', 1, '2020-01-01 00:00:00', 12, 0.0, '2025-11-10 09:16:36'),
('MGR001', 'manager', '$2y$10$BT6yImKCAbgJ5swoRChxweMAy6Vk4vWY4N9354qfCJsHcIJdiVyJe', 'Lê Văn Quản lý', 'kdarknessk1402@gmail.com', 'Trưởng khoa', 'Khoa Công nghệ', 3, '2019-09-01 00:00:00', 12, 0.0, '2025-11-10 09:16:36'),
('USER001', 'user1', '$2y$10$weY6fDQ0faZzBkEY32lK3u3IIKTEASoGccyvMMYkJItgxTqD7XdQm', 'Trần Thị User', 'thbao.thuduc@gmail.com', 'Giảng viên', 'Khoa Công nghệ', 2, '2021-06-15 00:00:00', 12, 2.0, '2025-11-10 09:16:36');

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

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cauhinhemail`
--
ALTER TABLE `cauhinhemail`
  ADD PRIMARY KEY (`MaCauHinh`);

--
-- Chỉ mục cho bảng `donnghiphep`
--
ALTER TABLE `donnghiphep`
  ADD PRIMARY KEY (`MaDon`),
  ADD KEY `MaNguoiDung` (`MaNguoiDung`);

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
-- Chỉ mục cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`MaNguoiDung`),
  ADD UNIQUE KEY `TenDangNhap` (`TenDangNhap`),
  ADD KEY `MaVaiTro` (`MaVaiTro`);

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
-- AUTO_INCREMENT cho bảng `lichdaybu`
--
ALTER TABLE `lichdaybu`
  MODIFY `MaLich` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lichsuemail`
--
ALTER TABLE `lichsuemail`
  MODIFY `MaLichSu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  ADD CONSTRAINT `donnghiphep_ibfk_1` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE;

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
-- Các ràng buộc cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD CONSTRAINT `nguoidung_ibfk_1` FOREIGN KEY (`MaVaiTro`) REFERENCES `vaitro` (`MaVaiTro`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
