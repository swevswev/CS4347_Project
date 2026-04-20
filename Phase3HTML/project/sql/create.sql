-- Hospital records — physical schema for CS 4347-style project
-- MySQL 8.x compatible. Run after creating database: CREATE DATABASE hospital_records CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS visit_doctors;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS conditions;
DROP TABLE IF EXISTS departments;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE departments (
  department_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dept_name VARCHAR(100) NOT NULL,
  dept_location VARCHAR(100) NULL,
  PRIMARY KEY (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctors (
  doctor_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  doctor_name VARCHAR(100) NOT NULL,
  specialization VARCHAR(100) NULL,
  department_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (doctor_id),
  CONSTRAINT fk_doctors_department
    FOREIGN KEY (department_id) REFERENCES departments (department_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conditions (
  condition_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  condition_name VARCHAR(100) NOT NULL,
  PRIMARY KEY (condition_id),
  UNIQUE KEY uq_conditions_name (condition_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patients (
  patient_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(100) NOT NULL,
  age INT UNSIGNED NOT NULL,
  gender VARCHAR(50) NOT NULL,
  ins_type VARCHAR(50) NULL,
  provider VARCHAR(100) NULL,
  deductible DECIMAL(12,2) NULL,
  primary_doctor_id INT UNSIGNED NULL,
  PRIMARY KEY (patient_id),
  CONSTRAINT fk_patients_primary_doctor
    FOREIGN KEY (primary_doctor_id) REFERENCES doctors (doctor_id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visits (
  visit_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patient_id INT UNSIGNED NOT NULL,
  condition_id INT UNSIGNED NULL,
  procedure_text VARCHAR(100) NULL,
  cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  length_of_stay INT UNSIGNED NULL,
  satisfaction TINYINT UNSIGNED NULL,
  outcome VARCHAR(50) NULL,
  read_admission TINYINT(1) NULL,
  visit_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (visit_id),
  CONSTRAINT fk_visits_patient
    FOREIGN KEY (patient_id) REFERENCES patients (patient_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_visits_condition
    FOREIGN KEY (condition_id) REFERENCES conditions (condition_id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit_doctors (
  visit_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (visit_id, doctor_id),
  CONSTRAINT fk_vd_visit
    FOREIGN KEY (visit_id) REFERENCES visits (visit_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_vd_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors (doctor_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
