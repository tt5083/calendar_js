-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-29 03:40:12
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `stcalendar`
--

-- --------------------------------------------------------

--
-- 資料表結構 `calendar_events`
--

CREATE TABLE `calendar_events` (
  `event_id` int(11) NOT NULL COMMENT '編號',
  `event_publisher` varchar(50) NOT NULL COMMENT '發佈人姓名',
  `created_time` datetime DEFAULT current_timestamp() COMMENT '發佈時間',
  `update_time` datetime NOT NULL DEFAULT current_timestamp() COMMENT '更新時間',
  `event_title` varchar(255) NOT NULL COMMENT '活動名稱',
  `event_start_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT '活動日期，例如 2025-12-03',
  `event_end_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT '結束時間/活動結束',
  `event_date` date DEFAULT NULL COMMENT '整理為活動日',
  `event_location` varchar(50) NOT NULL COMMENT '活動位置',
  `event_category` varchar(50) NOT NULL COMMENT '活動類別',
  `event_organizer` varchar(50) NOT NULL COMMENT '承辦單位',
  `event_implementer` varchar(50) NOT NULL COMMENT '承辦人',
  `event_lector` varchar(50) NOT NULL COMMENT '講師',
  `event_note` varchar(200) NOT NULL COMMENT '備註'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `calendar_events`
--

INSERT INTO `calendar_events` (`event_id`, `event_publisher`, `created_time`, `update_time`, `event_title`, `event_start_date`, `event_end_date`, `event_date`, `event_location`, `event_category`, `event_organizer`, `event_implementer`, `event_lector`, `event_note`) VALUES
(1, 'Peter', '2025-12-29 09:36:53', '2025-12-29 10:04:15', '測試001，活動為今天日期', '2025-12-29 00:00:00', '2025-12-29 10:12:31', NULL, '育樂教室', '教育', '外單位', 'A', 'Peter', 'test');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_date` (`event_start_date`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '編號', AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
