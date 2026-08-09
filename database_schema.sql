CREATE DATABASE IF NOT EXISTS gitaloy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gitaloy;

-- ------------------------------------------------------------------
-- Geography (admin managed)
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS divisions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS districts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  division_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  CONSTRAINT fk_districts_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS upazilas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  district_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  CONSTRAINT fk_upazilas_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS villages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  upazila_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  CONSTRAINT fk_villages_upazila FOREIGN KEY (upazila_id) REFERENCES upazilas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Structure
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS academic_years (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS centers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  village_id INT DEFAULT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_centers_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Users (1 admin, 2 divisional manager, 3 district manager,
-- 4 accountant, 5 teacher, 6 student/guardian)
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(100) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL DEFAULT 5,
  phone VARCHAR(30) DEFAULT NULL,
  scope_type ENUM('division','district') DEFAULT NULL,
  scope_id INT DEFAULT NULL,
  center_id INT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(50) NOT NULL UNIQUE,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  guardian_name VARCHAR(100) DEFAULT NULL,
  guardian_phone VARCHAR(30) DEFAULT NULL,
  dob DATE DEFAULT NULL,
  gender ENUM('Male','Female') DEFAULT NULL,
  village_id INT DEFAULT NULL,
  center_id INT NOT NULL,
  class_id INT DEFAULT NULL,
  admission_date DATE DEFAULT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_students_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
  CONSTRAINT fk_students_center FOREIGN KEY (center_id) REFERENCES centers(id),
  CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sdocuments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Academics
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS syllabuses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT DEFAULT NULL,
  subject_id INT DEFAULT NULL,
  year_id INT DEFAULT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT DEFAULT NULL,
  term VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_syllabus_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_syllabus_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_syllabus_year FOREIGN KEY (year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  center_id INT NOT NULL,
  year_id INT DEFAULT NULL,
  teacher_id INT DEFAULT NULL,
  session_date DATE NOT NULL,
  type ENUM('Friday','Weekly') NOT NULL DEFAULT 'Friday',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sessions_center FOREIGN KEY (center_id) REFERENCES centers(id),
  CONSTRAINT fk_sessions_year FOREIGN KEY (year_id) REFERENCES academic_years(id) ON DELETE SET NULL,
  CONSTRAINT fk_sessions_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  student_id INT NOT NULL,
  status ENUM('Present','Absent') NOT NULL DEFAULT 'Present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attendance_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  UNIQUE KEY uq_attendance (session_id, student_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Money
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fee_heads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  head_id INT DEFAULT NULL,
  year_id INT DEFAULT NULL,
  period_type ENUM('Friday','Monthly') NOT NULL DEFAULT 'Friday',
  session_id INT DEFAULT NULL,
  month VARCHAR(7) DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('Paid','Partial','Unpaid') NOT NULL DEFAULT 'Unpaid',
  due_date DATE DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fees_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_fees_head FOREIGN KEY (head_id) REFERENCES fee_heads(id) ON DELETE SET NULL,
  CONSTRAINT fk_fees_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL,
  CONSTRAINT fk_fees_year FOREIGN KEY (year_id) REFERENCES academic_years(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  center_id INT DEFAULT NULL,
  year_id INT DEFAULT NULL,
  user_id INT DEFAULT NULL,
  category VARCHAR(100) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  expense_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_expenses_center FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE SET NULL,
  CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Performance
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT DEFAULT NULL,
  subject_id INT DEFAULT NULL,
  year_id INT DEFAULT NULL,
  teacher_id INT DEFAULT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  total_marks INT NOT NULL DEFAULT 10,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tasks_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS task_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  student_id INT NOT NULL,
  obtained_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  remarks VARCHAR(255) DEFAULT NULL,
  marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tresults_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_tresults_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  UNIQUE KEY uq_task_student (task_id, student_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Distribution
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS distribution_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  unit VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS distribution_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  scope_type ENUM('division','district') DEFAULT NULL,
  scope_id INT DEFAULT NULL,
  item_id INT DEFAULT NULL,
  quantity INT NOT NULL DEFAULT 0,
  year_id INT DEFAULT NULL,
  status ENUM('Planned','In Progress','Completed') NOT NULL DEFAULT 'Planned',
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dplans_item FOREIGN KEY (item_id) REFERENCES distribution_items(id) ON DELETE SET NULL,
  CONSTRAINT fk_dplans_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS distributions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plan_id INT DEFAULT NULL,
  student_id INT NOT NULL,
  item_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  distributed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  added_by INT DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  CONSTRAINT fk_distributions_plan FOREIGN KEY (plan_id) REFERENCES distribution_plans(id) ON DELETE CASCADE,
  CONSTRAINT fk_distributions_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_distributions_item FOREIGN KEY (item_id) REFERENCES distribution_items(id) ON DELETE RESTRICT,
  CONSTRAINT fk_distributions_user FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  skey VARCHAR(50) NOT NULL UNIQUE,
  svalue TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Activity log (site-wide audit trail shown in the admin panel)
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  user_name VARCHAR(100) DEFAULT NULL,
  role_id INT DEFAULT NULL,
  module VARCHAR(50) DEFAULT NULL,
  description VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------
-- Roles & permissions (admin can create roles and assign actions)
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pkey VARCHAR(100) NOT NULL UNIQUE,
  label VARCHAR(150) NOT NULL,
  pgroup VARCHAR(100) NOT NULL,
  sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO roles (id, name, slug, description, is_system) VALUES
  (1, 'Admin', 'admin', 'Global access to everything.', 1),
  (2, 'Divisional Manager', 'div_manager', 'All modules across their division.', 1),
  (3, 'District Manager', 'dist_manager', 'All modules across their district.', 1),
  (4, 'Accountant', 'accountant', 'Fees, expenses, attendance, students, reports.', 1),
  (5, 'Teacher', 'teacher', 'Students, sessions, attendance, fees, tasks at their center.', 1),
  (6, 'Student', 'student', 'Own profile, progress, and report card.', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug);

INSERT INTO permissions (id, pkey, label, pgroup, sort) VALUES
  (1, 'dashboard.view', 'View dashboard', 'Dashboard', 10),
  (2, 'students.view', 'View students', 'Students', 10),
  (3, 'students.manage', 'Add & edit students', 'Students', 20),
  (4, 'students.delete', 'Delete students', 'Students', 30),
  (5, 'attendance.view', 'View sessions & attendance', 'Attendance', 10),
  (6, 'attendance.manage', 'Create sessions & mark attendance', 'Attendance', 20),
  (7, 'fees.view', 'View fees & expenses', 'Fees', 10),
  (8, 'fees.manage', 'Record payments & expenses', 'Fees', 20),
  (9, 'syllabus.view', 'View syllabuses', 'Syllabus', 10),
  (10, 'syllabus.manage', 'Manage syllabuses', 'Syllabus', 20),
  (11, 'tasks.view', 'View tasks & marks', 'Tasks', 10),
  (12, 'tasks.manage', 'Manage tasks & marks', 'Tasks', 20),
  (13, 'progress.view', 'View progress', 'Progress', 10),
  (14, 'distribution.view', 'View distribution plans', 'Distribution', 10),
  (15, 'distribution.manage', 'Manage distribution', 'Distribution', 20),
  (16, 'reports.view', 'View & print reports', 'Reports', 10),
  (17, 'users.manage', 'Manage user accounts', 'Users', 10),
  (18, 'roles.manage', 'Manage roles & permissions', 'Roles & Permissions', 10),
  (19, 'settings.manage', 'Manage site settings', 'Site Settings', 10),
  (20, 'content.manage', 'Manage frontend content', 'Frontend Content', 10),
  (21, 'admin.geography', 'Geography setup', 'Admin Setup', 10),
  (22, 'admin.centers', 'Centers setup', 'Admin Setup', 20),
  (23, 'admin.classes', 'Classes, subjects & years', 'Admin Setup', 30),
  (24, 'admin.fees', 'Fee heads & items', 'Admin Setup', 40)
ON DUPLICATE KEY UPDATE label = VALUES(label), pgroup = VALUES(pgroup), sort = VALUES(sort);

INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
  (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
  (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20),
  (1, 21), (1, 22), (1, 23), (1, 24),
  (2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10),
  (2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17),
  (3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 9), (3, 10),
  (3, 11), (3, 12), (3, 13), (3, 14), (3, 15), (3, 16), (3, 17),
  (4, 1), (4, 2), (4, 3), (4, 4), (4, 5), (4, 6), (4, 7), (4, 8), (4, 9), (4, 10),
  (4, 11), (4, 12), (4, 13), (4, 16), (4, 17),
  (5, 1), (5, 2), (5, 3), (5, 4), (5, 5), (5, 6), (5, 7), (5, 8), (5, 9), (5, 10),
  (5, 11), (5, 12), (5, 13), (5, 16),
  (6, 1);
