CREATE TABLE `migration` (
  `migratedCommit` varchar(40) NOT NULL,
  `datetime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`migratedCommit`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1