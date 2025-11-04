
-- users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(100),
    photo VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    location VARCHAR(100),
    github VARCHAR(255),
    summary TEXT,
    languages TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- personal_info table
CREATE TABLE IF NOT EXISTS personal_info (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    field_name VARCHAR(100) NOT NULL,
    field_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- education table
CREATE TABLE IF NOT EXISTS education (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    degree VARCHAR(100) NOT NULL,
    school VARCHAR(255) NOT NULL,
    dates VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- skills table
CREATE TABLE IF NOT EXISTS skills (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    skill_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- projects table
CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    project_name VARCHAR(255) NOT NULL,
    project_link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- project_technologies table (for many-to-many relationship)
CREATE TABLE IF NOT EXISTS project_technologies (
    id SERIAL PRIMARY KEY,
    project_id INTEGER REFERENCES projects(id) ON DELETE CASCADE,
    technology VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default user (password is '1234' hashed)
INSERT INTO users (username, password, name, title, photo, email, phone, location, github, summary, languages) 
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 1234
    'Paul Andrew Macatangay',
    'Computer Science Student',
    'paul-photo.jpg',
    'paulandrewcmacatangay@gmail.com',
    '+63 991 241 8619',
    'Lobo, Batangas',
    'https://github.com/paulmacatangay',
    'Aspiring software engineer passionate about technology, programming, and continuous learning. Dedicated to developing efficient and user-friendly solutions.',
    'Filipino – Native,English – Proficient'
) ON CONFLICT (username) DO NOTHING;

-- Insert personal information
INSERT INTO personal_info (user_id, field_name, field_value) VALUES
(1, 'Date of Birth', 'June 2, 2005'),
(1, 'Place of Birth', 'Batangas City'),
(1, 'Civil Status', 'Single'),
(1, 'Field of Specialization', 'Computer Science');

-- Insert education records
INSERT INTO education (user_id, degree, school, dates) VALUES
(1, 'Elementary', 'Lobo Central School', '2017'),
(1, 'Secondary', 'Lord Immanuel Institute Foundation, Inc.', '2023'),
(1, 'Tertiary', 'Batangas State University', 'Present');

-- Insert skills
INSERT INTO skills (user_id, skill_name) VALUES
(1, 'PHP'),
(1, 'HTML/CSS'),
(1, 'JavaScript'),
(1, 'MySQL'),
(1, 'Laravel'),
(1, 'Git');

-- Insert projects
INSERT INTO projects (user_id, project_name, project_link) VALUES
(1, 'bookXpress: Book Rental System', 'https://github.com/paulmacatangay/bookXpress'),
(1, 'Johnson''s Algorithm with Pairing Heap', 'https://github.com/paulmacatangay/DAA-Source-Code'),
(1, 'EcoMap', 'https://github.com/Andaljc1218/ECO-MAP');

-- Insert project technologies
INSERT INTO project_technologies (project_id, technology) VALUES
(1, 'Java'),
(1, 'MySQL'),
(2, 'Python'),
(3, 'HTML'),
(3, 'CSS'),
(3, 'MySQL');
