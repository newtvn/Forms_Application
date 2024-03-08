-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 07, 2024 at 08:05 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sky_survey_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `form`
--

CREATE TABLE `form` (
  `FormID` int(11) NOT NULL,
  `FormName` varchar(255) DEFAULT NULL,
  `FormType` varchar(255) DEFAULT NULL,
  `FormDescription` longtext DEFAULT NULL,
  `DateCreated` datetime DEFAULT NULL,
  `DateModified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `QuestionID` int(11) NOT NULL,
  `FormID` int(11) DEFAULT NULL,
  `QuestionText` varchar(255) DEFAULT NULL,
  `QuestionName` varchar(255) DEFAULT NULL,
  `QuestionType` varchar(255) DEFAULT NULL,
  `QuestionRequired` tinyint(1) DEFAULT NULL,
  `QuestionDescription` longtext DEFAULT NULL,
  `DateCreated` datetime DEFAULT NULL,
  `DateModified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_choice`
--

CREATE TABLE `question_choice` (
  `ChoiceID` int(11) NOT NULL,
  `QuestionID` int(11) DEFAULT NULL,
  `ChoiceValue` text DEFAULT NULL,
  `ChoiceDescription` varchar(255) DEFAULT NULL,
  `DateCreated` datetime DEFAULT NULL,
  `DateAttached` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_choice_parameters`
--

CREATE TABLE `question_choice_parameters` (
  `OptionID` int(11) NOT NULL,
  `QuestionID` int(11) DEFAULT NULL,
  `Multiple` enum('Yes','No') NOT NULL,
  `DateCreated` datetime DEFAULT NULL,
  `DateAttached` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_file_type`
--

CREATE TABLE `question_file_type` (
  `FileTypeID` int(11) NOT NULL,
  `QuestionID` int(11) DEFAULT NULL,
  `FileName` varchar(255) DEFAULT NULL,
  `FilePath` varchar(255) DEFAULT NULL,
  `DateAttached` datetime DEFAULT NULL,
  `DateModified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_file_type_parameters`
--

CREATE TABLE `question_file_type_parameters` (
  `FileTypeID` int(11) NOT NULL,
  `QuestionID` int(11) NOT NULL,
  `Multiple` enum('Yes','No') NOT NULL,
  `FileName` varchar(255) DEFAULT NULL,
  `MaxFileSize` int(11) DEFAULT NULL,
  `MaxFileUnit` int(11) DEFAULT NULL,
  `DateAttached` datetime DEFAULT NULL,
  `DateModified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE `responses` (
  `ResponsesID` int(11) NOT NULL,
  `QuestionResponse` varchar(255) DEFAULT NULL,
  `FormID` int(11) DEFAULT NULL,
  `QuestionID` int(11) DEFAULT NULL,
  `DateCreated` datetime DEFAULT NULL,
  `DateModified` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `form`
--
ALTER TABLE `form`
  ADD PRIMARY KEY (`FormID`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`QuestionID`),
  ADD KEY `FormID` (`FormID`);

--
-- Indexes for table `question_choice`
--
ALTER TABLE `question_choice`
  ADD PRIMARY KEY (`ChoiceID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `question_choice_parameters`
--
ALTER TABLE `question_choice_parameters`
  ADD PRIMARY KEY (`OptionID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `question_file_type`
--
ALTER TABLE `question_file_type`
  ADD PRIMARY KEY (`FileTypeID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `question_file_type_parameters`
--
ALTER TABLE `question_file_type_parameters`
  ADD PRIMARY KEY (`FileTypeID`,`QuestionID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`ResponsesID`),
  ADD KEY `FormID` (`FormID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `form`
--
ALTER TABLE `form`
  MODIFY `FormID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_choice`
--
ALTER TABLE `question_choice`
  MODIFY `ChoiceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_choice_parameters`
--
ALTER TABLE `question_choice_parameters`
  MODIFY `OptionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_file_type`
--
ALTER TABLE `question_file_type`
  MODIFY `FileTypeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `responses`
--
ALTER TABLE `responses`
  MODIFY `ResponsesID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`FormID`) REFERENCES `form` (`FormID`);

--
-- Constraints for table `question_choice`
--
ALTER TABLE `question_choice`
  ADD CONSTRAINT `question_choice_ibfk_1` FOREIGN KEY (`QuestionID`) REFERENCES `questions` (`QuestionID`);

--
-- Constraints for table `question_choice_parameters`
--
ALTER TABLE `question_choice_parameters`
  ADD CONSTRAINT `question_choice_parameters_ibfk_1` FOREIGN KEY (`QuestionID`) REFERENCES `questions` (`QuestionID`);

--
-- Constraints for table `question_file_type`
--
ALTER TABLE `question_file_type`
  ADD CONSTRAINT `question_file_type_ibfk_1` FOREIGN KEY (`QuestionID`) REFERENCES `questions` (`QuestionID`);

--
-- Constraints for table `question_file_type_parameters`
--
ALTER TABLE `question_file_type_parameters`
  ADD CONSTRAINT `question_file_type_parameters_ibfk_1` FOREIGN KEY (`FileTypeID`) REFERENCES `question_file_type` (`FileTypeID`),
  ADD CONSTRAINT `question_file_type_parameters_ibfk_2` FOREIGN KEY (`QuestionID`) REFERENCES `questions` (`QuestionID`);

--
-- Constraints for table `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`FormID`) REFERENCES `form` (`FormID`),
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`QuestionID`) REFERENCES `questions` (`QuestionID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
