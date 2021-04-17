-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2021 at 12:39 PM
-- Server version: 10.1.29-MariaDB
-- PHP Version: 7.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `audio`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_validation`
--

CREATE TABLE `email_validation` (
  `id` int(11) NOT NULL,
  `email` varchar(99) NOT NULL,
  `code` int(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `email_validation`
--

INSERT INTO `email_validation` (`id`, `email`, `code`, `expires_at`) VALUES
(2, 'Loremt@g.com', 443598, '0000-00-00 00:00:00'),
(3, 'Loremt1@g.com', 425121, '0000-00-00 00:00:00'),
(6, 'Loremt6@g.com', 528955, '2021-04-14 09:26:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(99) NOT NULL,
  `lastname` varchar(99) NOT NULL,
  `email` varchar(99) NOT NULL,
  `email_valid` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(255) NOT NULL,
  `subscribed` int(11) NOT NULL DEFAULT '0',
  `trial` tinyint(1) NOT NULL DEFAULT '0',
  `decryted` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `email_valid`, `password`, `subscribed`, `trial`, `decryted`) VALUES
(1, 'world', 'nice', 'Lorem@g.com', 0, '$2y$10$sqN9EgaHb9Voxo9eN7Wfj.sQ/c2a5S31U9oc1gLBxtH4E29XO2yZC', 0, 0, 0),
(2, 'world', 'nice', 'Lorem1@g.com', 1, '$2y$10$KiMuJVBaXw4WVC1tDDWqGeMUnRzuTu2qltsBQ2GKXhDOMex2KN.5u', 307, 1, 1),
(3, 'world', 'nice', 'Loremt@g.com', 0, '$2y$10$0rV5GXNTZPjXsiMYekm7d.uQlRzw/H6nmzKCH7N5NAvaCk1KVsF4m', 0, 0, 0),
(4, 'world', 'nice', 'Loremt1@g.com', 0, '$2y$10$2jKoOMMQ2e8FNn/jSr21VuF0q7ujWjtgt12fJErvwB7v1iRu1FPq.', 0, 0, 0),
(5, 'world', 'nice', 'Loremt2@g.com', 0, '$2y$10$vBvv5U1L5bksvbZ5br26.OjaVVgoiz3ZUCfN5Vm61MYLH9T2VSSK2', 0, 0, 0),
(6, 'world', 'nice', 'Loremt3@g.com', 0, '$2y$10$TW/yNQ8ytg9ccnKY0WDRUuspAQkHa5ijFX0mydgflJ5VE/G.N7PbW', 0, 0, 0),
(7, 'world', 'nice', 'Loremt4@g.com', 0, '$2y$10$wthVOsR2h0dL.nZ16.Z8MeIAJjaQGPlbyagfC3YqIYKvaFZshTaBS', 0, 0, 0),
(8, 'world', 'nice', 'Loremt5@g.com', 0, '$2y$10$o19mzHXlLQ6mh0r85FbUo.WgvvugQfXv3znlUQjRyuPx2wdS4rIUe', 0, 0, 0),
(9, 'world', 'nice', 'Loremt6@g.com', 0, '$2y$10$xrm70Yn9FLnNDNqQ3K67sOxQB2otma0i7C6J3qDaLjEb/ICz.mBeC', 0, 0, 0),
(10, 'Albert', 'Duro', 'slyboydon1@gmail.com', 1, '$2y$10$ZAB9CZOX9O5SYrvxrwlkIeqUSWOhSlPYMLOnWym6we2EwGb3gvHCa', 14, 1, 1),
(11, 'Albert', 'Duro', 'begededum4bakel@gmail.com', 1, '$2y$10$ozcQGwDeMDo4pzWEwNzn7uFS9VmSHUxjCRBe6NBtJlDKy9DZmQoOa', 0, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_validation`
--
ALTER TABLE `email_validation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_validation`
--
ALTER TABLE `email_validation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
