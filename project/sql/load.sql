-- load.sql
-- run this after create.sql -- loads all the dat files into the db
-- order matters here bc of fk constraints, cant just load in any order
-- also make sure local_infile is turned on first:
-- SET GLOBAL local_infile = 1;
-- --------------------------------------------------------
-- departments go first -- doctors table referances this
-- --------------------------------------------------------
LOAD DATA LOCAL INFILE 'project/sql/departments.dat'
INTO TABLE DEPARTMENTS
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(Department_ID, Department_Name, Location);
-- --------------------------------------------------------
-- doctors next, depends on departements being loaded
-- --------------------------------------------------------
LOAD DATA LOCAL INFILE 'project/sql/doctors.dat'
INTO TABLE DOCTORS
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(Doctor_ID, Doctor_Name, Specialization, Department_ID);
-- --------------------------------------------------------
-- conditions is standalone so order doesnt really matter
-- --------------------------------------------------------
LOAD DATA LOCAL INFILE 'project/sql/conditions.dat'
INTO TABLE CONDITIONS
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(Condition_ID, Condition_Name);
-- --------------------------------------------------------
-- patients references doctors (prim_doctor fk) so load after
-- --------------------------------------------------------
LOAD DATA LOCAL INFILE 'project/sql/patients.dat'
INTO TABLE PATIENTS
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(Patient_ID, Age, Gender, Ins_Type, Provider, Deductible, Prim_Doctor)
SET Full_Name = CONCAT('Patient ', Patient_ID);
-- --------------------------------------------------------
-- visits depends on both patients and doctors being there
-- --------------------------------------------------------
LOAD DATA LOCAL INFILE 'project/sql/visits.dat'
INTO TABLE VISITS
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(Visit_ID, Patient_ID, Doctor_ID, Satisfaction, Procedure_Name, Cost, Length_of_Stay, Re_Admission, Outcome);
-- --------------------------------------------------------
-- visits_conditions is the junction table, load this last
-- both visit_id and condition_id need to exist already
-- --------------------------------------------------------
LOAD DATA LOCAL INFILE 'project/sql/visits_conditions.dat'
INTO TABLE VISITS_CONDITIONS
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(Visit_ID, Condition_ID);