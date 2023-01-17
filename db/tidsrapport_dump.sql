/*
SQLyog Community
MySQL - 5.7.36 : Database - tidsrapport
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `kategorier` */

CREATE TABLE `kategorier` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Kategori` varchar(25) COLLATE utf8_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uix_k` (`Kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

/*Data for the table `kategorier` */

insert  into `kategorier`(`ID`,`Kategori`) values 
(1,'DÅ'),
(4,'HEJ'),
(2,'KALLE'),
(3,'NEJ');

/*Table structure for table `uppgifter` */

CREATE TABLE `uppgifter` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Tid` time NOT NULL,
  `Datum` date NOT NULL,
  `KategoriID` int(11) NOT NULL,
  `Beskrivning` varchar(255) COLLATE utf8_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `KategoriID` (`KategoriID`),
  CONSTRAINT `uppgifter_ibfk_1` FOREIGN KEY (`KategoriID`) REFERENCES `kategorier` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

/*Data for the table `uppgifter` */

insert  into `uppgifter`(`ID`,`Tid`,`Datum`,`KategoriID`,`Beskrivning`) values 
(1,'00:45:00','2023-01-01',1,'det gick bra'),
(2,'00:20:00','2023-01-26',4,'det gick rätt så jätte bra'),
(3,'03:23:00','2023-01-20',2,'väldigt fin kstt'),
(4,'03:21:00','2023-01-12',1,'kalle'),
(5,'04:02:00','2023-01-05',2,'hejsan');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
