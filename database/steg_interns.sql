PRAGMA foreign_keys=ON;
BEGIN TRANSACTION;

-- Departments
CREATE TABLE departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);
INSERT INTO departments(id,name) VALUES
 (1,'Informatique'),
 (2,'Finance'),
 (3,'Ressources Humaines'),
 (4,'Production');

-- Users (normalized to department_id)
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    user_type TEXT NOT NULL,
    email TEXT NOT NULL,
    full_name TEXT NOT NULL,
    department_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    login_attempts INTEGER DEFAULT 0,
    locked_until DATETIME,
    director_id INTEGER,
    FOREIGN KEY(department_id) REFERENCES departments(id)
);
INSERT INTO users(id,username,password,user_type,email,full_name,department_id,created_at,last_login,login_attempts,locked_until,director_id) VALUES
 (1,'admin','$2y$10$leY58HHiUkvR3UjMbTs7aus40RElqXEZLkdin57j65zr4AdAMzvfa','admin','admin@steg.tn','Administrateur STEG',1,'2025-09-03 16:32:38','2025-09-03 16:33:33',0,NULL,NULL),
 (2,'director1','$2y$10$NtMFav27p8f3O8v4Zgi.SuQplfNDsTecxlA075j5H1ndg6WcP4OFK','director','director@steg.tn','Directeur Informatique',1,'2025-09-03 16:32:38','2025-09-03 16:33:58',0,NULL,NULL),
 (3,'supervisor1','$2y$10$/LTbZT/wpq..u7FZ3PIeoOJJB6uOFtf4bQYcEDaSwxZoJC.he8w4O','supervisor','samira.bensalah@steg.tn','Samira Ben Salah',1,'2025-09-03 16:32:38','2025-09-03 16:32:56',0,NULL,2),
 (4,'supervisor2','$2y$10$OR/07Z4fTSvHLgjVJD601ulv4o3HTYhNZD7HUQHOaf0uvGFQUVt5O','supervisor','mohamed.trabelsi@steg.tn','Mohamed Trabelsi',2,'2025-09-03 16:32:38',NULL,0,NULL,NULL),
 (5,'supervisor3','$2y$10$sbJuzUCqwgSsCkqqeb6WVujj6vzSNH4fwoMsonAysQUu9mqGGBO5u','supervisor','fatma.khalil@steg.tn','Fatma Khalil',3,'2025-09-03 16:32:38',NULL,0,NULL,NULL),
 (6,'intern1','$2y$10$A0N7Lb/cvO4JESyTxMHxBue8RPLzv8Uy.lmz.xGgYBQSMvZkfGCnC','intern','firas.welhazi@email.com','Firas Welhazi',1,'2025-09-03 16:32:38','2025-09-03 16:33:10',0,NULL,NULL),
 (7,'intern2','$2y$10$l5z4Ph8MzSal4FNklUsCJuxljSenT.iyHdOGfE3gCVz1wP1po8m5a','intern','samira.khedher@email.com','Samira Khedher',3,'2025-09-03 16:32:38',NULL,0,NULL,NULL),
 (8,'intern3','$2y$10$.cI1EFR3aeudP.4w591A3.wUD3AuLN5PWAQJqnFu8A9U3TB8yvTDe','intern','ahmed.bensalah@email.com','Ahmed Ben Salah',2,'2025-09-03 16:32:38',NULL,0,NULL,NULL);

-- Interns (normalized to department_id)
CREATE TABLE interns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT NOT NULL,
    id_card TEXT NOT NULL,
    university TEXT NOT NULL,
    department_id INTEGER NOT NULL,
    supervisor_id INTEGER,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(supervisor_id) REFERENCES users(id),
    FOREIGN KEY(department_id) REFERENCES departments(id)
);
INSERT INTO interns(id,user_id,first_name,last_name,email,id_card,university,department_id,supervisor_id,start_date,end_date,status,created_at) VALUES
 (1,6,'Firas','Welhazi','firas.welhazi@email.com','12345678','Université de Tunis',1,3,'2025-06-01','2025-08-31','active','2025-09-03 16:32:38'),
 (2,7,'Samira','Khedher','samira.khedher@email.com','87654321','ENIT',3,5,'2025-07-01','2025-09-30','active','2025-09-03 16:32:38'),
 (3,8,'Ahmed','Ben Salah','ahmed.bensalah@email.com','23456789','ISG Tunis',2,4,'2025-05-01','2025-07-31','active','2025-09-03 16:32:38');

-- Reports, Evaluations, Attestations
CREATE TABLE reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    intern_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'submitted',
    FOREIGN KEY(intern_id) REFERENCES interns(id)
);

CREATE TABLE evaluations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    intern_id INTEGER NOT NULL,
    supervisor_id INTEGER NOT NULL,
    score INTEGER NOT NULL,
    notes TEXT,
    date TEXT DEFAULT CURRENT_DATE,
    FOREIGN KEY(intern_id) REFERENCES interns(id),
    FOREIGN KEY(supervisor_id) REFERENCES users(id)
);

CREATE TABLE attestations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    intern_id INTEGER NOT NULL,
    generated_by INTEGER NOT NULL,
    generation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_by_email BOOLEAN DEFAULT 0,
    downloaded BOOLEAN DEFAULT 0,
    FOREIGN KEY(intern_id) REFERENCES interns(id),
    FOREIGN KEY(generated_by) REFERENCES users(id)
);

COMMIT;
