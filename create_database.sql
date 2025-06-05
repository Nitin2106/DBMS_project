-- Create the database
DROP DATABASE IF EXISTS students;
CREATE DATABASE students;
USE students;

-- Create register table for user authentication
CREATE TABLE register (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    cpassword VARCHAR(255) NOT NULL,
    user_type ENUM('student', 'mentor', 'admin') DEFAULT 'student',
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    dob DATE NOT NULL
);

-- Create course_details table
CREATE TABLE course_details (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_duration VARCHAR(50),
    course_description TEXT
);

-- Create student_details table
CREATE TABLE student_details (
    stud_id INT AUTO_INCREMENT PRIMARY KEY,
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15),
    gender VARCHAR(10),
    dob DATE,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    nationality VARCHAR(50),
    course_id INT,
    FOREIGN KEY (course_id) REFERENCES course_details(course_id),
    FOREIGN KEY (email) REFERENCES register(email)
);

-- Create mentors table
CREATE TABLE mentors (
    mentor_id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_name VARCHAR(100) NOT NULL,
    mentor_email VARCHAR(100) NOT NULL UNIQUE,
    mentor_password VARCHAR(255) NOT NULL,
    mentor_department VARCHAR(50),
    mentor_mobile VARCHAR(15),
    joining_date DATE
);

-- Create student_mentors table for mentor-student relationships
CREATE TABLE student_mentors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    mentor_id INT,
    assignment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id),
    FOREIGN KEY (mentor_id) REFERENCES mentors(mentor_id),
    UNIQUE KEY unique_student_mentor (stud_id, mentor_id)
);

-- Create subjects table
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    course_id INT,
    credits INT,
    description TEXT,
    FOREIGN KEY (course_id) REFERENCES course_details(course_id)
);

-- Create subject_attendance table
CREATE TABLE subject_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    stud_id INT,
    total_classes INT DEFAULT 0,
    attended_classes INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id),
    UNIQUE KEY unique_subject_student (subject_id, stud_id)
);

-- Create subject_grades table
CREATE TABLE subject_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT,
    stud_id INT,
    assignment_score DECIMAL(5,2) DEFAULT 0,
    midterm_score DECIMAL(5,2) DEFAULT 0,
    final_score DECIMAL(5,2) DEFAULT 0,
    total_score DECIMAL(5,2) DEFAULT 0,
    grade_letter CHAR(2),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id),
    UNIQUE KEY unique_subject_student_grade (subject_id, stud_id)
);

-- Create mentor_notes table
CREATE TABLE mentor_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    mentor_id INT,
    note_content TEXT,
    note_type ENUM('Academic', 'Behavioral', 'General') DEFAULT 'General',
    note_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id),
    FOREIGN KEY (mentor_id) REFERENCES mentors(mentor_id)
);

-- Create academic_progress table
CREATE TABLE academic_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    semester INT,
    cgpa DECIMAL(3,2),
    academic_standing VARCHAR(50),
    remarks TEXT,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id)
);

-- Create student_achievements table
CREATE TABLE student_achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    achievement_title VARCHAR(200),
    achievement_date DATE,
    description TEXT,
    awarded_by VARCHAR(100),
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id)
);

-- Insert default course
INSERT INTO course_details (course_id, course_name, course_duration, course_description) 
VALUES (101, 'B.Tech Computer Science', '4 years', 'Bachelor of Technology in Computer Science and Engineering');

-- Insert default subjects for course 101
INSERT INTO subjects (subject_name, course_id, credits) VALUES 
('Linear Algebra', 101, 4),
('DBMS', 101, 4),
('Data Structures', 101, 4),
('Entrepreneurial Mindset', 101, 3),
('Embedded Systems', 101, 4),
('Operating Systems', 101, 4),
('Yoga', 101, 2),
('English', 101, 3);

-- Create attendance table (legacy - keeping for backward compatibility)
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    total_classes INT DEFAULT 0,
    attended_classes INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id)
);

-- Create grades table (legacy - keeping for backward compatibility)
CREATE TABLE grades (
    grade_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    course_id INT,
    grade INT,
    FOREIGN KEY (stud_id) REFERENCES student_details(stud_id),
    FOREIGN KEY (course_id) REFERENCES course_details(course_id)
); 