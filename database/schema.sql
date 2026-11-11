-- NVRO original schema. Non-destructive: existing tables are retained.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `brand` (
  `Brand_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Brand_Name` varchar(50) NOT NULL,
  `Country_Of_Origin` varchar(50) NOT NULL,
  PRIMARY KEY (`Brand_ID`),
  UNIQUE KEY `uq_brand_name` (`Brand_Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `customer` (
  `Customer_ID` int(11) NOT NULL AUTO_INCREMENT,
  `First_Name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Contact_Number` varchar(15) NOT NULL,
  `Address` text NOT NULL,
  PRIMARY KEY (`Customer_ID`),
  UNIQUE KEY `uq_customer_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `employee` (
  `Employee_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID` int(11) DEFAULT NULL,
  `First_Name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Contact_Number` varchar(15) NOT NULL,
  `Position` varchar(50) NOT NULL,
  PRIMARY KEY (`Employee_ID`),
  UNIQUE KEY `uq_employee_user` (`User_ID`),
  CONSTRAINT `fk_employee_user` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `inquiry` (
  `Inquiry_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Customer_ID` int(11) NOT NULL,
  `Vehicle_ID` int(11) NOT NULL,
  `Employee_ID` int(11) DEFAULT NULL,
  `Inquiry_Date` datetime NOT NULL DEFAULT current_timestamp(),
  `Inquiry_Message` text NOT NULL,
  `Inquiry_Status` enum('Pending','In Progress','Resolved','Cancelled') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`Inquiry_ID`),
  KEY `idx_inquiry_customer` (`Customer_ID`),
  KEY `idx_inquiry_vehicle` (`Vehicle_ID`),
  KEY `idx_inquiry_employee` (`Employee_ID`),
  KEY `idx_inquiry_date` (`Inquiry_Date`),
  CONSTRAINT `fk_inquiry_customer` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_inquiry_employee` FOREIGN KEY (`Employee_ID`) REFERENCES `employee` (`Employee_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inquiry_vehicle` FOREIGN KEY (`Vehicle_ID`) REFERENCES `vehicle` (`Vehicle_ID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sold_vehicle` (
  `Sold_Vehicle_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Vehicle_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Employee_ID` int(11) NOT NULL,
  `Sale_Date` date NOT NULL,
  `Selling_Price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`Sold_Vehicle_ID`),
  UNIQUE KEY `uq_sold_vehicle_vehicle` (`Vehicle_ID`),
  KEY `idx_sold_vehicle_customer` (`Customer_ID`),
  KEY `idx_sold_vehicle_employee` (`Employee_ID`),
  KEY `idx_sold_vehicle_sale_date` (`Sale_Date`),
  CONSTRAINT `fk_sold_vehicle_customer` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sold_vehicle_employee` FOREIGN KEY (`Employee_ID`) REFERENCES `employee` (`Employee_ID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sold_vehicle_vehicle` FOREIGN KEY (`Vehicle_ID`) REFERENCES `vehicle` (`Vehicle_ID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `test_drive` (
  `Test_Drive_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Customer_ID` int(11) NOT NULL,
  `Vehicle_ID` int(11) NOT NULL,
  `Employee_ID` int(11) DEFAULT NULL,
  `Scheduled_Date` datetime NOT NULL,
  `Request_Status` enum('Pending','Approved','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`Test_Drive_ID`),
  KEY `idx_test_drive_customer` (`Customer_ID`),
  KEY `idx_test_drive_vehicle` (`Vehicle_ID`),
  KEY `idx_test_drive_employee` (`Employee_ID`),
  KEY `idx_test_drive_date` (`Scheduled_Date`),
  CONSTRAINT `fk_test_drive_customer` FOREIGN KEY (`Customer_ID`) REFERENCES `customer` (`Customer_ID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_test_drive_employee` FOREIGN KEY (`Employee_ID`) REFERENCES `employee` (`Employee_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_test_drive_vehicle` FOREIGN KEY (`Vehicle_ID`) REFERENCES `vehicle` (`Vehicle_ID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `user` (
  `User_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `Password_Hash` varchar(255) NOT NULL,
  `Role` enum('Administrator','Dealership Staff') NOT NULL,
  `Account_Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`User_ID`),
  UNIQUE KEY `uq_user_username` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `vehicle` (
  `Vehicle_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Brand_ID` int(11) NOT NULL,
  `Model` varchar(50) NOT NULL,
  `Year` year(4) NOT NULL,
  `Color` varchar(30) NOT NULL,
  `Transmission` enum('Automatic','Manual','CVT') NOT NULL,
  `Fuel_Type` enum('Gasoline','Diesel','Hybrid','Electric') NOT NULL,
  `Price` decimal(12,2) NOT NULL,
  `Image_Path` varchar(255) DEFAULT 'default.jpg',
  `Availability_Status` enum('Available','Reserved','Sold') NOT NULL DEFAULT 'Available',
  PRIMARY KEY (`Vehicle_ID`),
  KEY `idx_vehicle_brand` (`Brand_ID`),
  KEY `idx_vehicle_model` (`Model`),
  KEY `idx_vehicle_year` (`Year`),
  KEY `idx_vehicle_status` (`Availability_Status`),
  CONSTRAINT `fk_vehicle_brand` FOREIGN KEY (`Brand_ID`) REFERENCES `brand` (`Brand_ID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

