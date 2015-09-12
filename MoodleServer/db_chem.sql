SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`laboratoryPractice`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`laboratoryPractice` (
  `practiceid` INT NOT NULL AUTO_INCREMENT,
  `title` TEXT NOT NULL,
  `description` TEXT NULL,
  `level` INT NULL,
  `practicedate` DATE NULL,
  PRIMARY KEY (`practiceid`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`studentRecord`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`studentRecord` (
  `studentid` INT NOT NULL AUTO_INCREMENT,
  `practiceid` INT NOT NULL,
  `username` VARCHAR(45) NULL,
  `score` INT NULL,
  INDEX `fk_studentRecord_laboratoryPractice_idx` (`practiceid` ASC),
  PRIMARY KEY (`studentid`),
  CONSTRAINT `fk_studentRecord_laboratoryPractice`
    FOREIGN KEY (`practiceid`)
    REFERENCES `mydb`.`laboratoryPractice` (`practiceid`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
