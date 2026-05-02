<?php

if (!function_exists('get_active_academic_year_id')) {
    function get_active_academic_year_id($conn) {
        static $active_year_id = null;
        static $loaded = false;

        if ($loaded) {
            return $active_year_id;
        }

        $loaded = true;
        $result = $conn->query("SELECT academic_year_id FROM academic_year WHERE status = 'Active' ORDER BY academic_year_id DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $active_year_id = (int) $row['academic_year_id'];
        } else {
            $active_year_id = 0;
        }

        return $active_year_id;
    }
}

if (!function_exists('class_student_count_sql')) {
    function class_student_count_sql($class_alias = 'c') {
        return "(SELECT COUNT(DISTINCT se.student_id)
                  FROM student_enrollment se
                 WHERE se.section_id = {$class_alias}.section_id
                   AND se.academic_year_id = {$class_alias}.academic_year_id)";
    }
}

if (!function_exists('class_enrollment_join_condition')) {
    function class_enrollment_join_condition($class_alias = 'c', $enrollment_alias = 'se') {
        return "{$enrollment_alias}.section_id = {$class_alias}.section_id AND {$enrollment_alias}.academic_year_id = {$class_alias}.academic_year_id";
    }
}

