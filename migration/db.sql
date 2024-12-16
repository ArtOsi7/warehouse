-- Dumping database structure for warehouse1
CREATE DATABASE IF NOT EXISTS `warehouse1` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `warehouse1`;

-- Dumping structure for table warehouse1.ramp
CREATE TABLE IF NOT EXISTS `ramp` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(255) NOT NULL DEFAULT '',
    `name` varchar(255) NOT NULL DEFAULT '',
    `worktime` varchar(300) NOT NULL DEFAULT '',
    `priority` tinyint(4) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- Dumping data for table warehouse1.ramp: ~2 rows (approximately)
/*!40000 ALTER TABLE `ramp` DISABLE KEYS */;
INSERT INTO `ramp` (`id`, `code`, `name`, `worktime`, `priority`) VALUES
(1, 'R1007', 'RampOne', '{"1":{"open":"8:00","close":"17:00"},"2":{"open":"8:00","close":"17:00"},"3":{"open":"8:00","close":"17:00"},"4":{"open":"8:00","close":"17:00"},"5":{"open":"8:00","close":"17:00"}}', 1);
INSERT INTO `ramp` (`id`, `code`, `name`, `worktime`, `priority`) VALUES
(2, 'R2007', 'RampTwo', '{"1":{"open":"8:00","close":"17:00"},"2":{"open":"8:00","close":"17:00"},"3":{"open":"8:00","close":"17:00"},"4":{"open":"8:00","close":"17:00"},"5":{"open":"8:00","close":"17:00"}}', 2);
/*!40000 ALTER TABLE `ramp` ENABLE KEYS */;

-- Dumping structure for table warehouse1.reservations
CREATE TABLE IF NOT EXISTS `reservations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `reservation_from` datetime NOT NULL,
    `reservation_till` datetime NOT NULL,
    `car_number` varchar(50) NOT NULL,
    `ramp_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `FK__ramp` (`ramp_id`),
    CONSTRAINT `FK__ramp` FOREIGN KEY (`ramp_id`) REFERENCES `ramp` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table warehouse1.reservations: ~0 rows (approximately)
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
