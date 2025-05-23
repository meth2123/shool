-- Création de la table student_teacher_course
CREATE TABLE `student_teacher_course` (
    `id` int NOT NULL AUTO_INCREMENT,
    `student_id` varchar(20) CHARACTER SET utf8mb3 NOT NULL,
    `teacher_id` varchar(20) CHARACTER SET utf8mb3 NOT NULL,
    `course_id` int NOT NULL,
    `class_id` varchar(20) CHARACTER SET utf8mb3 NOT NULL,
    `created_by` varchar(20) CHARACTER SET utf8mb3 NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`class_id`) REFERENCES `class` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_assignment` (`student_id`, `teacher_id`, `course_id`, `class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3; 