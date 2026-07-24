CREATE DATABASE IF NOT EXISTS study_budy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE study_budy_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'faculty', 'principle') DEFAULT 'student',
    primary_subject VARCHAR(50) NULL,
    bio TEXT,
    credits INT DEFAULT 0,
    upload_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE faculty_additional_subjects (
    user_id INT NOT NULL,
    subject VARCHAR(50) NOT NULL,
    PRIMARY KEY (user_id, subject),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    size INT NOT NULL,
    subject VARCHAR(50) NOT NULL,
    tag VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    downloads INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (document_id, user_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (document_id, user_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed Data
INSERT INTO users (username, email, password, role, primary_subject) VALUES 
('principle', 'principle@example.edu', '$2y$10$0XsToJq.0Nci2xXHWZBRP.OsYKbcm8K01b/WwWsO1U3ym2joP4Rbu', 'principle', NULL),
('faculty_FCSN', 'fcsn@example.edu', '$2y$10$wPlY2UyIOp.tXWl5x/P3J.FJMrWlgNKWRXqqwBTGc8cg/TEWOAyA6', 'faculty', 'FCSN'),
('faculty_DSDA', 'dsda@example.edu', '$2y$10$ujNQxnyDEGItTM7fII8sI.ZNUH8WN3uCAulnLSb4xvpW85xKXoPka', 'faculty', 'DSDA'),
('faculty_FCPP', 'fcpp@example.edu', '$2y$10$qT1JnQk2iXZLNyVRsZhjLucFoG/tYcEilECIFawOZZk6JazKup/8W', 'faculty', 'FCPP'),
('faculty_EP', 'ep@example.edu', '$2y$10$RVmr44lqh0XHCdhbmW.PM.jLkHZ6Bd3n1jQjRNrZTJExwibIf7DsK', 'faculty', 'Physics'),
('faculty_EM', 'em@example.edu', '$2y$10$7ffaKx3x1xgEc19jAsotjes7d7e7RwRR8pEgaEm3VMIHdGMLqz19u', 'faculty', 'EM-2'),
('student', 'student@example.edu', '$2y$10$./CvAz82X9n.9ndLsg4k1OC4CuMXwxBUgaSOGlpkbGkEFPzKeX9gS', 'student', NULL);
