-- create.sql
-- sets up all the tables for the hospital records system
-- run this on a fresh db before loading any data
-- --------------------------------------------------------
-- patients table -- core entity, every visit points back here
-- prim_doctor is nullable bc patient might not have one assigned yet
-- --------------------------------------------------------
CREATE TABLE PATIENTS(
 Patient_ID INT PRIMARY KEY,
 Full_Name VARCHAR(100) NOT NULL,
 Age INT NOT NULL,
 Gender VARCHAR(20) NOT NULL,
 Ins_Type VARCHAR(50), -- type of insurance (ppo, hmo, etc)
 Provider VARCHAR(100), -- insurance company name
 Deductible DECIMAL(10,2), -- deductable amount, can be null
 Prim_Doctor INT -- fk to doctors, set later
);
-- --------------------------------------------------------
-- departments -- things like cardiology, emergency, etc
-- location is optional, some places dont track this
-- --------------------------------------------------------
CREATE TABLE DEPARTMENTS(
 Department_ID INT PRIMARY KEY,
 Department_Name VARCHAR(100) NOT NULL,
 Location VARCHAR(100) -- wing/floor, nullable
);
-- --------------------------------------------------------
-- doctors -- each one belongs to exactly one departement
-- --------------------------------------------------------
CREATE TABLE DOCTORS(
 Doctor_ID INT PRIMARY KEY,
 Doctor_Name VARCHAR(100) NOT NULL,
 Specialization VARCHAR(100), -- can be null if general practitoner
 Department_ID INT NOT NULL -- fk to departments
);
-- --------------------------------------------------------
-- conditions -- lookup table so we dont repeat strings in visits
-- things like diabetes, heart disease, stroke, etc
-- --------------------------------------------------------
CREATE TABLE CONDITIONS(
 Condition_ID INT PRIMARY KEY,
 Condition_Name VARCHAR(100) NOT NULL
);
-- --------------------------------------------------------
-- visits -- the main table, one row per hospital encounter
-- cost defaults to 0 just in case it gets entered later
-- --------------------------------------------------------
CREATE TABLE VISITS(
 Visit_ID INT PRIMARY KEY,
 Patient_ID INT NOT NULL, -- fk to patients
 Doctor_ID INT NOT NULL, -- fk to doctors
 Satisfaction INT, -- score 1-10, optional
 Procedure_Name VARCHAR(100), -- what was done during the visit
 Cost DECIMAL(10,2) DEFAULT 0.00,
 Length_of_Stay INT, -- days stayed, can be null
 Re_Admission BOOLEAN, -- was patient readmitted
 Outcome VARCHAR(50) -- discharged, improved, etc
);
-- --------------------------------------------------------
-- visits_conditions -- m:n junction between visits and conditions
-- a single visit can have multiple conditions and vice versa
-- --------------------------------------------------------
CREATE TABLE VISITS_CONDITIONS(
 Visit_ID INT, -- fk to visits
 Condition_ID INT, -- fk to conditions
 PRIMARY KEY (Visit_ID, Condition_ID)
);
-- --------------------------------------------------------
-- foreign key constraints -- added after table creation
-- --------------------------------------------------------
-- patients.prim_doctor -> doctors
ALTER TABLE PATIENTS
ADD FOREIGN KEY (Prim_Doctor) REFERENCES DOCTORS(Doctor_ID);
-- doctors.department_id -> departments
ALTER TABLE DOCTORS
ADD FOREIGN KEY (Department_ID) REFERENCES DEPARTMENTS(Department_ID);
-- visits.patient_id -> patients
ALTER TABLE VISITS
ADD FOREIGN KEY (Patient_ID) REFERENCES PATIENTS(Patient_ID);
-- visits.doctor_id -> doctors
ALTER TABLE VISITS
ADD FOREIGN KEY (Doctor_ID) REFERENCES DOCTORS(Doctor_ID);
-- junction table fks
ALTER TABLE VISITS_CONDITIONS
ADD FOREIGN KEY (Visit_ID) REFERENCES VISITS(Visit_ID);
ALTER TABLE VISITS_CONDITIONS
ADD FOREIGN KEY (Condition_ID) REFERENCES CONDITIONS(Condition_ID);