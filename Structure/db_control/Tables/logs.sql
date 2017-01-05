CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migrationCommit` varchar(40) DEFAULT NULL,
  `filePath` varchar(200) DEFAULT NULL,
  `statusCode` varchar(10) DEFAULT NULL,
  `statusDesc` text,
  `datetime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `commit` (`migrationCommit`),
  KEY `datetime` (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1