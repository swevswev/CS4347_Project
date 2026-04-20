-- Sample seed data (tab/comma style loads optional; INSERTs work everywhere)
SET NAMES utf8mb4;

INSERT INTO departments (dept_name, dept_location) VALUES
  ('Emergency', 'Building A — Level 1'),
  ('Cardiology', 'Building B — Level 3'),
  ('Internal Medicine', 'Building B — Level 2');

INSERT INTO doctors (doctor_name, specialization, department_id) VALUES
  ('Dr. Avery Chen', 'Emergency medicine', 1),
  ('Dr. Jordan Mills', 'Cardiology', 2),
  ('Dr. Sam Rivera', 'General internal medicine', 3);

INSERT INTO conditions (condition_name) VALUES
  ('Hypertension'),
  ('Type 2 diabetes'),
  ('Asthma'),
  ('Fracture — lower limb');

INSERT INTO patients (full_name, age, gender, ins_type, provider, deductible, primary_doctor_id) VALUES
  ('Alex Morgan', 34, 'Non-binary', 'PPO', 'BlueCare', 500.00, 3),
  ('Jamie Lee', 62, 'Female', 'Medicare', 'CMS', NULL, 2);

INSERT INTO visits (patient_id, condition_id, procedure_text, cost, length_of_stay, satisfaction, outcome, read_admission) VALUES
  (1, 1, 'Blood pressure check', 125.50, 0, 9, 'Stable', 0),
  (2, 4, 'X-ray and splint', 890.00, 1, 8, 'Discharged', 0);

INSERT INTO visit_doctors (visit_id, doctor_id) VALUES
  (1, 3),
  (2, 1),
  (2, 2);
