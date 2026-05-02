-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2026 at 02:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `assessment_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_year`
--

CREATE TABLE `academic_year` (
  `academic_year_id` int(11) NOT NULL,
  `year_name` varchar(15) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_year`
--

INSERT INTO `academic_year` (`academic_year_id`, `year_name`, `start_date`, `end_date`, `status`) VALUES
(1, '2025-2026', '2025-06-01', '2026-03-31', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`class_id`, `academic_year_id`, `user_id`, `subject_id`, `section_id`) VALUES
(1, 1, 2, 1, 1),
(2, 1, 3, 2, 2),
(3, 1, 4, 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `competency_tags`
--

CREATE TABLE `competency_tags` (
  `competency_id` int(11) NOT NULL,
  `curriculum_id` int(11) DEFAULT NULL,
  `competency_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competency_tags`
--

INSERT INTO `competency_tags` (`competency_id`, `curriculum_id`, `competency_name`) VALUES
(1, 1, 'Fractions'),
(2, 1, 'Integers'),
(3, 1, 'Algebraic Expressions'),
(4, 1, 'Linear Equations'),
(5, 1, 'Geometry'),
(6, 2, 'Grammar'),
(7, 2, 'Vocabulary'),
(8, 2, 'Reading Comprehension'),
(9, 2, 'Context Clues'),
(10, 2, 'Main Idea'),
(11, 3, 'Biology'),
(12, 3, 'Matter'),
(13, 3, 'Earth Science'),
(14, 3, 'Force and Motion'),
(15, 3, 'Ecosystems'),
(16, 4, 'Linear Equations'),
(17, 4, 'Factoring'),
(18, 4, 'Geometry'),
(19, 4, 'Probability'),
(20, 4, 'Statistics'),
(21, 5, 'Subject-Verb Agreement'),
(22, 5, 'Sentence Structure'),
(23, 5, 'Summarizing'),
(24, 5, 'Inference'),
(25, 5, 'Supporting Details'),
(26, 6, 'Physics'),
(27, 6, 'Chemistry'),
(28, 6, 'Earthquakes'),
(29, 6, 'Waves'),
(30, 6, 'Energy'),
(31, 7, 'Quadratic Equations'),
(32, 7, 'Functions'),
(33, 7, 'Polynomials'),
(34, 7, 'Radicals'),
(35, 7, 'Coordinate Geometry'),
(36, 8, 'Literary Elements'),
(37, 8, 'Figurative Language'),
(38, 8, 'Writing Organization'),
(39, 8, 'Paragraph Development'),
(40, 8, 'Argumentative Writing'),
(41, 9, 'Chemical Reactions'),
(42, 9, 'Electricity'),
(43, 9, 'Genetics'),
(44, 9, 'Plate Tectonics'),
(45, 9, 'Climate'),
(46, 10, 'Polynomials'),
(47, 10, 'Trigonometry'),
(48, 10, 'Statistics'),
(49, 10, 'Probability'),
(50, 10, 'Sequences'),
(51, 11, 'Research Writing'),
(52, 11, 'Persuasive Writing'),
(53, 11, 'Literary Criticism'),
(54, 11, 'Public Speaking'),
(55, 11, 'Textual Analysis'),
(56, 12, 'Genetics'),
(57, 12, 'Evolution'),
(58, 12, 'Ecosystems'),
(59, 12, 'Chemical Bonding'),
(60, 12, 'Electricity and Magnetism');

-- --------------------------------------------------------

--
-- Table structure for table `curriculum`
--

CREATE TABLE `curriculum` (
  `curriculum_id` int(11) NOT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `curriculum`
--

INSERT INTO `curriculum` (`curriculum_id`, `grade_level_id`, `subject_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 2, 1),
(5, 2, 2),
(6, 2, 3),
(7, 3, 1),
(8, 3, 2),
(9, 3, 3),
(10, 4, 1),
(11, 4, 2),
(12, 4, 3);

-- --------------------------------------------------------

--
-- Table structure for table `grade_level`
--

CREATE TABLE `grade_level` (
  `grade_level_id` int(11) NOT NULL,
  `grade_level_name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_level`
--

INSERT INTO `grade_level` (`grade_level_id`, `grade_level_name`) VALUES
(1, 'Grade 7'),
(2, 'Grade 8'),
(3, 'Grade 9'),
(4, 'Grade 10'),
(10, 'Grade 11');

-- --------------------------------------------------------

--
-- Table structure for table `school_profile`
--

CREATE TABLE `school_profile` (
  `school_id` varchar(20) NOT NULL,
  `school_name` varchar(50) NOT NULL,
  `region_division` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int(11) NOT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `grade_level_id`, `section_name`) VALUES
(1, 1, 'Aguinaldo'),
(2, 2, 'Bonifacio'),
(3, 3, 'Mabini'),
(4, 4, 'Luna'),
(6, 10, 'Del Pilar');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `student_LRN` varchar(12) DEFAULT NULL,
  `first_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_LRN`, `first_name`, `last_name`, `gender`) VALUES
(1, '100000000001', 'Juan', 'Dela Cruz', 'male'),
(2, '100000000002', 'Maria', 'Santos', 'female'),
(3, '100000000003', 'Pedro', 'Penduko', 'male'),
(4, '100000000004', 'Ana', 'Reyes', 'female'),
(5, '100000000005', 'Jose', 'Protacio', 'male'),
(6, '200000000001', 'Ricardo', 'Dalisay', 'male'),
(7, '200000000002', 'Alyana', 'Arevalo', 'female'),
(8, '200000000003', 'Cardo', 'Dalisay', 'male'),
(9, '200000000004', 'Flora', 'Borja', 'female'),
(10, '200000000005', 'Benny', 'Sarmenta', 'male'),
(11, '300000000001', 'Jose', 'Rizal', 'male'),
(12, '300000000002', 'Leonor', 'Rivera', 'female'),
(13, '300000000003', 'Andres', 'Bonifacio', 'male'),
(14, '300000000004', 'Gregoria', 'De Jesus', 'female'),
(15, '300000000005', 'Apolinario', 'Mabini', 'male'),
(16, '400000000001', 'Ferdinand', 'Marcos', 'male'),
(17, '400000000002', 'Imelda', 'Marcos', 'female'),
(18, '400000000003', 'Cory', 'Aquino', 'female'),
(19, '400000000004', 'Benigno', 'Aquino', 'male'),
(20, '400000000005', 'Gloria', 'Arroyo', 'female'),
(21, '500000000001', 'Vic', 'Sotto', 'male'),
(22, '500000000002', 'Pauleen', 'Luna', 'female'),
(23, '500000000003', 'Joey', 'De Leon', 'male'),
(24, '500000000004', 'Eileen', 'Macapagal', 'female'),
(25, '500000000005', 'Tito', 'Sotto', 'male');

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollment`
--

CREATE TABLE `student_enrollment` (
  `student_enrollment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `academic_year_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_enrollment`
--

INSERT INTO `student_enrollment` (`student_enrollment_id`, `student_id`, `section_id`, `academic_year_id`) VALUES
(1, 1, 1, 1),
(2, 2, 1, 1),
(3, 3, 1, 1),
(4, 4, 1, 1),
(5, 5, 1, 1),
(6, 6, 2, 1),
(7, 7, 2, 1),
(8, 8, 2, 1),
(9, 9, 2, 1),
(10, 10, 2, 1),
(11, 11, 3, 1),
(12, 12, 3, 1),
(13, 13, 3, 1),
(14, 14, 3, 1),
(15, 15, 3, 1),
(16, 16, 4, 1),
(17, 17, 4, 1),
(18, 18, 4, 1),
(19, 19, 4, 1),
(20, 20, 4, 1),
(21, 21, 6, 1),
(22, 22, 6, 1),
(23, 23, 6, 1),
(24, 24, 6, 1),
(25, 25, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `subject_name` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `subject_code`, `subject_name`) VALUES
(1, 'MATH', 'Mathematics'),
(2, 'ENG', 'English'),
(3, 'SCI', 'Science');

-- --------------------------------------------------------

--
-- Table structure for table `sync_log`
--

CREATE TABLE `sync_log` (
  `sync_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sync_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `sync_status` enum('Success','Failed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE `test` (
  `test_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `test_name` varchar(50) DEFAULT NULL,
  `test_type` enum('Quiz','Exam','Diagnostic','Long Test') DEFAULT NULL,
  `test_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test`
--

INSERT INTO `test` (`test_id`, `class_id`, `test_name`, `test_type`, `test_date`) VALUES
(1, 3, 'Quiz1', 'Quiz', '2026-05-02'),
(2, 1, 'Quiz 1', 'Quiz', '2026-05-02'),
(3, 2, 'Quiz 1', 'Quiz', '2026-05-02');

-- --------------------------------------------------------

--
-- Table structure for table `test_item_result`
--

CREATE TABLE `test_item_result` (
  `item_result_id` int(11) NOT NULL,
  `test_part_id` int(11) DEFAULT NULL,
  `test_result_id` int(11) DEFAULT NULL,
  `item_number` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_item_result`
--

INSERT INTO `test_item_result` (`item_result_id`, `test_part_id`, `test_result_id`, `item_number`, `is_correct`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 1, 2, 0),
(3, 1, 1, 3, 0),
(4, 1, 1, 4, 0),
(5, 1, 1, 5, 0),
(6, 2, 1, 1, 1),
(7, 2, 1, 2, 0),
(8, 2, 1, 3, 0),
(9, 2, 1, 4, 0),
(10, 2, 1, 5, 0),
(11, 1, 2, 1, 0),
(12, 1, 2, 2, 0),
(13, 1, 2, 3, 0),
(14, 1, 2, 4, 1),
(15, 1, 2, 5, 1),
(16, 2, 2, 1, 0),
(17, 2, 2, 2, 1),
(18, 2, 2, 3, 0),
(19, 2, 2, 4, 1),
(20, 2, 2, 5, 0),
(21, 1, 3, 1, 0),
(22, 1, 3, 2, 0),
(23, 1, 3, 3, 0),
(24, 1, 3, 4, 1),
(25, 1, 3, 5, 0),
(26, 2, 3, 1, 1),
(27, 2, 3, 2, 1),
(28, 2, 3, 3, 1),
(29, 2, 3, 4, 1),
(30, 2, 3, 5, 0),
(31, 1, 4, 1, 0),
(32, 1, 4, 2, 1),
(33, 1, 4, 3, 1),
(34, 1, 4, 4, 1),
(35, 1, 4, 5, 1),
(36, 2, 4, 1, 0),
(37, 2, 4, 2, 0),
(38, 2, 4, 3, 1),
(39, 2, 4, 4, 1),
(40, 2, 4, 5, 1),
(41, 1, 5, 1, 1),
(42, 1, 5, 2, 1),
(43, 1, 5, 3, 1),
(44, 1, 5, 4, 0),
(45, 1, 5, 5, 0),
(46, 2, 5, 1, 1),
(47, 2, 5, 2, 0),
(48, 2, 5, 3, 1),
(49, 2, 5, 4, 0),
(50, 2, 5, 5, 0),
(51, 3, 6, 1, 0),
(52, 3, 6, 2, 0),
(53, 3, 6, 3, 1),
(54, 3, 6, 4, 1),
(55, 3, 6, 5, 0),
(56, 4, 6, 1, 0),
(57, 4, 6, 2, 0),
(58, 4, 6, 3, 1),
(59, 4, 6, 4, 0),
(60, 4, 6, 5, 1),
(61, 3, 7, 1, 1),
(62, 3, 7, 2, 1),
(63, 3, 7, 3, 0),
(64, 3, 7, 4, 0),
(65, 3, 7, 5, 1),
(66, 4, 7, 1, 0),
(67, 4, 7, 2, 0),
(68, 4, 7, 3, 0),
(69, 4, 7, 4, 0),
(70, 4, 7, 5, 1),
(71, 3, 8, 1, 1),
(72, 3, 8, 2, 0),
(73, 3, 8, 3, 0),
(74, 3, 8, 4, 1),
(75, 3, 8, 5, 1),
(76, 4, 8, 1, 0),
(77, 4, 8, 2, 0),
(78, 4, 8, 3, 0),
(79, 4, 8, 4, 0),
(80, 4, 8, 5, 1),
(81, 3, 9, 1, 1),
(82, 3, 9, 2, 1),
(83, 3, 9, 3, 0),
(84, 3, 9, 4, 1),
(85, 3, 9, 5, 1),
(86, 4, 9, 1, 1),
(87, 4, 9, 2, 0),
(88, 4, 9, 3, 0),
(89, 4, 9, 4, 0),
(90, 4, 9, 5, 0),
(91, 3, 10, 1, 1),
(92, 3, 10, 2, 1),
(93, 3, 10, 3, 1),
(94, 3, 10, 4, 1),
(95, 3, 10, 5, 0),
(96, 4, 10, 1, 1),
(97, 4, 10, 2, 0),
(98, 4, 10, 3, 0),
(99, 4, 10, 4, 0),
(100, 4, 10, 5, 0),
(101, 5, 11, 1, 0),
(102, 5, 11, 2, 1),
(103, 5, 11, 3, 0),
(104, 6, 11, 1, 0),
(105, 6, 11, 2, 1),
(106, 6, 11, 3, 0),
(107, 6, 11, 4, 0),
(108, 6, 11, 5, 1),
(109, 5, 12, 1, 1),
(110, 5, 12, 2, 1),
(111, 5, 12, 3, 0),
(112, 6, 12, 1, 0),
(113, 6, 12, 2, 0),
(114, 6, 12, 3, 0),
(115, 6, 12, 4, 0),
(116, 6, 12, 5, 0),
(117, 5, 13, 1, 0),
(118, 5, 13, 2, 1),
(119, 5, 13, 3, 1),
(120, 6, 13, 1, 0),
(121, 6, 13, 2, 0),
(122, 6, 13, 3, 1),
(123, 6, 13, 4, 1),
(124, 6, 13, 5, 0),
(125, 5, 14, 1, 1),
(126, 5, 14, 2, 1),
(127, 5, 14, 3, 1),
(128, 6, 14, 1, 0),
(129, 6, 14, 2, 0),
(130, 6, 14, 3, 1),
(131, 6, 14, 4, 0),
(132, 6, 14, 5, 1),
(133, 5, 15, 1, 1),
(134, 5, 15, 2, 1),
(135, 5, 15, 3, 1),
(136, 6, 15, 1, 0),
(137, 6, 15, 2, 1),
(138, 6, 15, 3, 0),
(139, 6, 15, 4, 0),
(140, 6, 15, 5, 0);

-- --------------------------------------------------------

--
-- Table structure for table `test_part`
--

CREATE TABLE `test_part` (
  `test_part_id` int(11) NOT NULL,
  `test_id` int(11) DEFAULT NULL,
  `competency_id` int(11) DEFAULT NULL,
  `part_order` varchar(15) DEFAULT NULL,
  `part_type` text DEFAULT NULL,
  `number_of_items` int(11) DEFAULT NULL,
  `points_per_item` int(11) DEFAULT NULL,
  `answer_key` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_part`
--

INSERT INTO `test_part` (`test_part_id`, `test_id`, `competency_id`, `part_order`, `part_type`, `number_of_items`, `points_per_item`, `answer_key`) VALUES
(1, 2, 3, 'Part I', 'Multiple Choice', 5, 1, 'A,A,B,C,D'),
(2, 2, 1, 'Part II', 'Multiple Choice', 5, 1, 'D,D,C,B,A'),
(3, 3, 22, 'Part 1', 'Multiple Choice', 5, 2, 'D,A,B,C,D'),
(4, 3, 21, 'Part 2', 'Multiple Choice', 5, 1, 'C,B,C,D,A'),
(5, 1, 45, 'Part 1', 'Multiple Choice', 3, 2, 'D,D,A'),
(6, 1, 42, 'Part 2', 'Multiple Choice', 5, 2, 'A,C,B,D,D');

-- --------------------------------------------------------

--
-- Table structure for table `test_result`
--

CREATE TABLE `test_result` (
  `test_result_id` int(11) NOT NULL,
  `test_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `mobile_uuid` varchar(100) DEFAULT NULL,
  `total_score` int(11) DEFAULT NULL,
  `raw_answers` text DEFAULT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_result`
--

INSERT INTO `test_result` (`test_result_id`, `test_id`, `student_id`, `mobile_uuid`, `total_score`, `raw_answers`, `checked_at`, `updated_at`) VALUES
(1, 2, 1, NULL, 2, '{\"1\":{\"1\":1,\"2\":0,\"3\":0,\"4\":0,\"5\":0},\"2\":{\"1\":1,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}', '2026-05-02 10:04:08', '2026-05-02 10:04:24'),
(2, 2, 3, NULL, 4, '{\"1\":{\"1\":0,\"2\":0,\"3\":0,\"4\":1,\"5\":1},\"2\":{\"1\":0,\"2\":1,\"3\":0,\"4\":1,\"5\":0}}', '2026-05-02 10:06:54', '2026-05-02 10:06:54'),
(3, 2, 5, NULL, 5, '{\"1\":{\"1\":0,\"2\":0,\"3\":0,\"4\":1,\"5\":0},\"2\":{\"1\":1,\"2\":1,\"3\":1,\"4\":1,\"5\":0}}', '2026-05-02 10:08:02', '2026-05-02 10:08:02'),
(4, 2, 4, NULL, 7, '{\"1\":{\"1\":0,\"2\":1,\"3\":1,\"4\":1,\"5\":1},\"2\":{\"1\":0,\"2\":0,\"3\":1,\"4\":1,\"5\":1}}', '2026-05-02 10:08:14', '2026-05-02 10:08:14'),
(5, 2, 2, NULL, 5, '{\"1\":{\"1\":1,\"2\":1,\"3\":1,\"4\":0,\"5\":0},\"2\":{\"1\":1,\"2\":0,\"3\":1,\"4\":0,\"5\":0}}', '2026-05-02 10:17:49', '2026-05-02 10:17:49'),
(6, 3, 7, NULL, 6, '{\"3\":{\"1\":0,\"2\":0,\"3\":1,\"4\":1,\"5\":0},\"4\":{\"1\":0,\"2\":0,\"3\":1,\"4\":0,\"5\":1}}', '2026-05-02 10:18:44', '2026-05-02 10:18:44'),
(7, 3, 9, NULL, 7, '{\"3\":{\"1\":1,\"2\":1,\"3\":0,\"4\":0,\"5\":1},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":1}}', '2026-05-02 10:18:57', '2026-05-02 10:18:57'),
(8, 3, 8, NULL, 7, '{\"3\":{\"1\":1,\"2\":0,\"3\":0,\"4\":1,\"5\":1},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":1}}', '2026-05-02 10:19:22', '2026-05-02 10:19:22'),
(9, 3, 6, NULL, 9, '{\"3\":{\"1\":1,\"2\":1,\"3\":0,\"4\":1,\"5\":1},\"4\":{\"1\":1,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}', '2026-05-02 10:19:37', '2026-05-02 10:19:37'),
(10, 3, 10, NULL, 9, '{\"3\":{\"1\":1,\"2\":1,\"3\":1,\"4\":1,\"5\":0},\"4\":{\"1\":1,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}', '2026-05-02 10:19:47', '2026-05-02 10:19:47'),
(11, 1, 13, NULL, 6, '{\"5\":{\"1\":0,\"2\":1,\"3\":0},\"6\":{\"1\":0,\"2\":1,\"3\":0,\"4\":0,\"5\":1}}', '2026-05-02 10:22:39', '2026-05-02 10:22:39'),
(12, 1, 14, NULL, 4, '{\"5\":{\"1\":1,\"2\":1,\"3\":0},\"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}', '2026-05-02 10:22:51', '2026-05-02 10:22:51'),
(13, 1, 15, NULL, 8, '{\"5\":{\"1\":0,\"2\":1,\"3\":1},\"6\":{\"1\":0,\"2\":0,\"3\":1,\"4\":1,\"5\":0}}', '2026-05-02 10:22:59', '2026-05-02 10:22:59'),
(14, 1, 12, NULL, 10, '{\"5\":{\"1\":1,\"2\":1,\"3\":1},\"6\":{\"1\":0,\"2\":0,\"3\":1,\"4\":0,\"5\":1}}', '2026-05-02 10:23:12', '2026-05-02 10:23:12'),
(15, 1, 11, NULL, 8, '{\"5\":{\"1\":1,\"2\":1,\"3\":1},\"6\":{\"1\":0,\"2\":1,\"3\":0,\"4\":0,\"5\":0}}', '2026-05-02 10:23:29', '2026-05-02 10:23:29');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `date_birth` date DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('principal','teacher') DEFAULT NULL,
  `status` enum('pending','active') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `first_name`, `last_name`, `gender`, `date_birth`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Admin', 'Principal', 'male', '1980-01-01', 'admin@school.edu.ph', '$2a$12$nZ76jzKHhsd.hPx5K/78YucxCPHK4O8VNSRp3mLI8gD52v4qqKAUG', 'principal', 'active', '2026-04-04 10:06:06'),
(2, 'Maria', 'Santos', 'female', '2001-06-22', 'maria@email.com', '$2y$10$2F4BNzFadFEINbOUbEL/yOkzoxj4Rx2ZtvWURCTDNaTl5fietJTOe', 'teacher', 'active', '2026-04-05 11:59:40'),
(3, 'Marciana', 'Añana', 'female', '1974-09-19', 'marciana@gmail.com', '$2y$10$12WHTP.dsFYDc5VambD0KencuKN2tZlbdAVLYpX6CcdG4I9LQrdM.', 'teacher', 'active', '2026-05-01 17:11:16'),
(4, 'John', 'Doe', 'male', '1995-10-18', 'johndoe@gmail.com', '$2y$10$YYC4q0KmI0WE8MdGx7/QeeZnj6229oPBU5voEudxbIWaE/xyO1R1W', 'teacher', 'active', '2026-05-02 08:28:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_year`
--
ALTER TABLE `academic_year`
  ADD PRIMARY KEY (`academic_year_id`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `competency_tags`
--
ALTER TABLE `competency_tags`
  ADD PRIMARY KEY (`competency_id`),
  ADD KEY `curriculum_id` (`curriculum_id`);

--
-- Indexes for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD PRIMARY KEY (`curriculum_id`),
  ADD KEY `grade_level_id` (`grade_level_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `grade_level`
--
ALTER TABLE `grade_level`
  ADD PRIMARY KEY (`grade_level_id`);

--
-- Indexes for table `school_profile`
--
ALTER TABLE `school_profile`
  ADD PRIMARY KEY (`school_id`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `grade_level_id` (`grade_level_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_LRN` (`student_LRN`);

--
-- Indexes for table `student_enrollment`
--
ALTER TABLE `student_enrollment`
  ADD PRIMARY KEY (`student_enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `sync_log`
--
ALTER TABLE `sync_log`
  ADD PRIMARY KEY (`sync_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `test_ibfk_1` (`class_id`);

--
-- Indexes for table `test_item_result`
--
ALTER TABLE `test_item_result`
  ADD PRIMARY KEY (`item_result_id`),
  ADD KEY `test_part_id` (`test_part_id`),
  ADD KEY `test_result_id` (`test_result_id`);

--
-- Indexes for table `test_part`
--
ALTER TABLE `test_part`
  ADD PRIMARY KEY (`test_part_id`),
  ADD KEY `competency_id` (`competency_id`),
  ADD KEY `test_part_ibfk_1` (`test_id`);

--
-- Indexes for table `test_result`
--
ALTER TABLE `test_result`
  ADD PRIMARY KEY (`test_result_id`),
  ADD UNIQUE KEY `mobile_uuid` (`mobile_uuid`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `test_result_ibfk_1` (`test_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_year`
--
ALTER TABLE `academic_year`
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `competency_tags`
--
ALTER TABLE `competency_tags`
  MODIFY `competency_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `curriculum`
--
ALTER TABLE `curriculum`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `grade_level`
--
ALTER TABLE `grade_level`
  MODIFY `grade_level_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `student_enrollment`
--
ALTER TABLE `student_enrollment`
  MODIFY `student_enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sync_log`
--
ALTER TABLE `sync_log`
  MODIFY `sync_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test`
--
ALTER TABLE `test`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `test_item_result`
--
ALTER TABLE `test_item_result`
  MODIFY `item_result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `test_part`
--
ALTER TABLE `test_part`
  MODIFY `test_part_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `test_result`
--
ALTER TABLE `test_result`
  MODIFY `test_result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `class_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_year` (`academic_year_id`),
  ADD CONSTRAINT `class_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `class_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`),
  ADD CONSTRAINT `class_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`);

--
-- Constraints for table `competency_tags`
--
ALTER TABLE `competency_tags`
  ADD CONSTRAINT `competency_tags_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`curriculum_id`);

--
-- Constraints for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD CONSTRAINT `curriculum_ibfk_1` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`grade_level_id`),
  ADD CONSTRAINT `curriculum_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`);

--
-- Constraints for table `section`
--
ALTER TABLE `section`
  ADD CONSTRAINT `section_ibfk_1` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`grade_level_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_enrollment`
--
ALTER TABLE `student_enrollment`
  ADD CONSTRAINT `student_enrollment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `student_enrollment_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`),
  ADD CONSTRAINT `student_enrollment_ibfk_3` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_year` (`academic_year_id`);

--
-- Constraints for table `sync_log`
--
ALTER TABLE `sync_log`
  ADD CONSTRAINT `sync_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `test`
--
ALTER TABLE `test`
  ADD CONSTRAINT `test_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE;

--
-- Constraints for table `test_item_result`
--
ALTER TABLE `test_item_result`
  ADD CONSTRAINT `test_item_result_ibfk_1` FOREIGN KEY (`test_part_id`) REFERENCES `test_part` (`test_part_id`),
  ADD CONSTRAINT `test_item_result_ibfk_2` FOREIGN KEY (`test_result_id`) REFERENCES `test_result` (`test_result_id`) ON DELETE CASCADE;

--
-- Constraints for table `test_part`
--
ALTER TABLE `test_part`
  ADD CONSTRAINT `test_part_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `test` (`test_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_part_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competency_tags` (`competency_id`);

--
-- Constraints for table `test_result`
--
ALTER TABLE `test_result`
  ADD CONSTRAINT `test_result_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `test` (`test_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_result_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
