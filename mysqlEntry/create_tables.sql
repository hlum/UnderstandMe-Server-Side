CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('student','teacher') NOT NULL,
    
    student_code VARCHAR(20) UNIQUE,   -- student unique code
    admission_year INT,
    major_code VARCHAR(10),
    photo_url VARCHAR(512),
    name VARCHAR(512),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Ensure student_code is only set for students 
    CONSTRAINT chk_student_fields CHECK (
        (role = 'student' AND student_code IS NOT NULL) OR 
        (role = 'teacher' AND student_code IS NULL)
    )
);


CREATE TABLE fcm_tokens(
	id CHAR(36) PRIMARY KEY,
	user_id CHAR(36) NOT NULL,
	device_id CHAR(36) NOT NULL,
	device_type VARCHAR(50),
	fcm_token TEXT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT unique_user_device UNIQUE (user_id, device_id)
);

CREATE TABLE classes (
    id CHAR(36) PRIMARY KEY,
    teacher_id CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    admission_year INT NOT NULL,
    major_code VARCHAR(10) NOT NULL,
    class_code VARCHAR(20) UNIQUE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_class UNIQUE (name, admission_year, major_code),
    CONSTRAINT fk_class_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE student_class_enrollments (
    id CHAR(36) PRIMARY KEY,
    student_id CHAR(36) NOT NULL,
    class_id CHAR(36) NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enroll_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,

    CONSTRAINT uq_student_class UNIQUE (student_id, class_id)
);


CREATE TABLE homeworks (
    id CHAR(36) PRIMARY KEY,
    teacher_id CHAR(36) NOT NULL,
    class_id CHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_homework_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_homework_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);


CREATE TABLE projects (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    homework_id CHAR(36) NOT NULL,
    github_file_link VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_project_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_homework FOREIGN KEY (homework_id) REFERENCES homeworks(id) ON DELETE CASCADE,
    UNIQUE (user_id, homework_id)
);



CREATE TABLE jobs (
    id CHAR(36) PRIMARY KEY,
    project_id CHAR(36) NOT NULL,
    status ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_job_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);


CREATE TABLE questions (
    id CHAR(36) PRIMARY KEY,
    job_id CHAR(36) NOT NULL,
    text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_question_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);


CREATE TABLE choices (
    id CHAR(36) PRIMARY KEY,
    question_id CHAR(36) NOT NULL,
    choice_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_choices_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);


CREATE TABLE answers (
    id CHAR(36) PRIMARY KEY,
    question_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    selected_choice_id CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_answers_user_question UNIQUE (user_id, question_id),

    CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_choice FOREIGN KEY (selected_choice_id) REFERENCES choices(id) ON DELETE CASCADE
);


CREATE TABLE results (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    homework_id CHAR(36) NOT NULL,
    total_questions INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    score INT NOT NULL,  -- e.g. 87.50
    evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_results_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_results_homework FOREIGN KEY (homework_id) REFERENCES homeworks(id) ON DELETE CASCADE,
    CONSTRAINT uq_result UNIQUE (user_id, homework_id)
);


CREATE OR REPLACE VIEW enrolled_students_for_class AS
SELECT 
    c.id AS class_id,
    u.id AS student_id,
    u.email,
    u.student_code AS user_student_id
FROM classes c
JOIN users u ON u.role = 'student'
WHERE 
    -- Normal class: class_code IS NULL (use year + major)
    (
        c.class_code IS NULL
        AND u.major_code = c.major_code
        AND u.admission_year = c.admission_year
    )
    OR
    -- Optional class: class_code IS NOT NULL (use enrollments)
    (
        c.class_code IS NOT NULL
        AND EXISTS (
            SELECT 1
            FROM student_class_enrollments e
            WHERE e.class_id = c.id
              AND e.student_id = u.id
        )
    );


 CREATE OR REPLACE VIEW homework_with_students AS
SELECT 
    h.id AS homework_id,
    h.class_id,
    h.teacher_id,
    h.title AS homework_title,
    h.description,
    h.due_date,
    h.created_at,
    es.student_id AS user_id,
    es.user_student_id AS user_student_id,
    es.email AS user_email
FROM homeworks h
JOIN enrolled_students_for_class es
    ON es.class_id = h.class_id;

 
 CREATE OR REPLACE VIEW homework_projects_jobs_results AS
SELECT
    hws.*,
    p.github_file_link,
    p.created_at AS submitted_at,
    j.status AS job_status,
    r.id AS result_id,
    COALESCE(r.score, 0) AS score
FROM homework_with_students hws
LEFT JOIN projects p
    ON p.homework_id = hws.homework_id
   AND p.user_id = hws.user_id
LEFT JOIN jobs j
    ON j.project_id = p.id
LEFT JOIN results r
    ON r.homework_id = hws.homework_id
   AND r.user_id = hws.user_id;




CREATE OR REPLACE VIEW homework_submission_status_per_user AS
SELECT 
    *,
    CASE
        WHEN github_file_link IS NULL THEN 'notAssigned'
        WHEN job_status IN ('pending','processing') THEN 'generatingQuestions'
        WHEN job_status = 'failed' THEN 'failed'
        WHEN job_status = 'done' AND result_id IS NULL THEN 'questionGenerated'
        WHEN job_status = 'done' AND result_id IS NOT NULL THEN 'completed'
        ELSE 'unknown'
    END AS submission_state
FROM homework_projects_jobs_results;



 
CREATE OR REPLACE VIEW questions_with_choices AS
SELECT 
    q.id AS question_id,
    q.job_id,
    j.project_id,
    p.homework_id,
    p.user_id,
    q.text AS question_text,
    c.id AS choice_id,
    c.choice_text,
    c.is_correct,
    q.created_at
FROM 
    questions q
LEFT JOIN 
    choices c ON q.id = c.question_id
LEFT JOIN 
    jobs j ON q.job_id = j.id
LEFT JOIN 
    projects p ON j.project_id = p.id;


