Hospital Records — CS 4347 style submission layout
==================================================

This folder matches the usual final zip layout:

  doc/       — Team PDFs, slides, and other documentation (add your files here).
  project/   — Everything needed to run the site: PHP, SQL, handlers, config.
  README.txt — This file (install + deploy). Keep it at the zip root.

REQUIREMENTS
  - MySQL 8.x (or compatible)
  - PHP 8.1+ with PDO MySQL (pdo_mysql)

-------------------------------------------------------------------
1) Local setup — database
-------------------------------------------------------------------
   From a MySQL client, use the SQL files under project/sql/:

   CREATE DATABASE hospital_records CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE hospital_records;
   SOURCE project/sql/create.sql;
   SOURCE project/sql/load.sql;

   (If your client runs with a different working directory, use the full path
   to each .sql file instead.)

-------------------------------------------------------------------
2) Local setup — PHP configuration
-------------------------------------------------------------------
   Copy project/includes/config.local.php.example to
   project/includes/config.local.php

   Edit host, dbname, user, pass for your local MySQL.

-------------------------------------------------------------------
3) Local setup — run the site
-------------------------------------------------------------------
   The web server’s document root must be the project/ folder (so index.php
   lives at the root of the URL).

   Option A — PHP built-in server (from inside project/):
      cd project
      php -S localhost:8080
      Open http://localhost:8080/index.php

   Option B — XAMPP:
      Copy or symlink the project folder into htdocs, e.g.
        C:\xampp\htdocs\hospital\
      so that C:\xampp\htdocs\hospital\index.php exists.
      Open http://localhost/hospital/index.php

-------------------------------------------------------------------
4) Deploy to InfinityFree (public URL for grading)
-------------------------------------------------------------------
   Upload the **contents** of project/ into your hosting htdocs (so index.php
   sits in the public web root, not nested inside a second “project” folder
   unless you want URLs like /project/index.php).

   A) Create the hosting account at https://www.infinityfree.net and open
      VistaPanel.

   B) Upload files using “Online File Manager” or FTP (often host
      ftpupload.net; use the FTP username/password from the panel).
      Upload everything inside project/: all .php files, handlers/,
      includes/, sql/.

   C) In VistaPanel → MySQL Databases, create a database and user. Note:
        MySQL hostname (often not “localhost”)
        Database name, username, password
      In phpMyAdmin, select that database and run project/sql/create.sql
      then project/sql/load.sql (paste or import).

   D) On the server, add project/includes/config.local.php with those four
      values. Do not commit real passwords to a public repo.

   E) Submit the site URL with /index.php, e.g.
      https://yoursite.epizy.com/index.php
      (use the exact hostname VistaPanel shows.)

   Notes: Free tiers may sleep or rate-limit. If SQL import fails, run
   create.sql in sections from the SQL tab.

-------------------------------------------------------------------
NOTES ON THE CODE
-------------------------------------------------------------------
   - Runnable app: only under project/ (*.php, includes/, handlers/, sql/).
   - archive/html_prototypes/ holds the original Phase 3 static HTML pages
     (not required to run the database site).
   - Admin: add departments before doctors; add doctors/conditions before
     patients/visits that reference them.
   - Foreign keys block deletes that would break integrity.

-------------------------------------------------------------------
FILES (under project/)
-------------------------------------------------------------------
   sql/create.sql, sql/load.sql  — schema and seed data
   includes/                     — PDO + config
   handlers/                     — POST handlers for forms
   *.php                         — pages and home
