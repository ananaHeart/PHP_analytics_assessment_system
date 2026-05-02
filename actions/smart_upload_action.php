<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

if (isset($_POST['upload_btn'])) {
    $file = $_FILES['csv_file']['tmp_name'] ?? '';

    $ay_id = get_active_academic_year_id($conn);
    if ($ay_id <= 0) {
        die("Error: No Active Academic Year found. Please add one in the database first.");
    }

    if ($file === '' || !is_uploaded_file($file)) {
        header("Location: ../pages/smart_import.php?upload=invalid_file");
        exit();
    }

    if (($handle = fopen($file, "r")) !== FALSE) {
        $conn->begin_transaction();

        try {
        fgetcsv($handle); // Skip the header row

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 6) {
                continue;
            }

            // New Column Order: 0:LRN, 1:First Name, 2:Last Name, 3:Gender, 4:Grade, 5:Section
            $lrn_raw = trim((string) $data[0]);
            $lrn_raw = preg_replace('/^\xEF\xBB\xBF/', '', $lrn_raw);
            $fname_raw = trim((string) $data[1]);
            $lname_raw = trim((string) $data[2]);
            $gender_raw = strtolower(trim((string) $data[3]));
            $g_name_raw = trim((string) $data[4]);
            $s_name_raw = trim((string) $data[5]);

            if ($lrn_raw === '' || $fname_raw === '' || $lname_raw === '' || $g_name_raw === '' || $s_name_raw === '') {
                continue;
            }

            $lrn    = mysqli_real_escape_string($conn, $lrn_raw);
            $fname  = mysqli_real_escape_string($conn, $fname_raw);
            $lname  = mysqli_real_escape_string($conn, $lname_raw);
            $gender = mysqli_real_escape_string($conn, $gender_raw === 'female' ? 'female' : 'male');
            $g_name = mysqli_real_escape_string($conn, $g_name_raw);
            $s_name = mysqli_real_escape_string($conn, $s_name_raw);

            // A. Handle Grade Level
            $check_g = $conn->query("SELECT grade_level_id FROM grade_level WHERE grade_level_name = '$g_name'");
            if ($check_g->num_rows > 0) {
                $g_id = $check_g->fetch_assoc()['grade_level_id'];
            } else {
                $conn->query("INSERT INTO grade_level (grade_level_name) VALUES ('$g_name')");
                $g_id = $conn->insert_id;
            }

            // B. Handle Section
            $check_s = $conn->query("SELECT section_id FROM section WHERE section_name = '$s_name' AND grade_level_id = '$g_id'");
            if ($check_s->num_rows > 0) {
                $s_id = $check_s->fetch_assoc()['section_id'];
            } else {
                $conn->query("INSERT INTO section (grade_level_id, section_name) VALUES ('$g_id', '$s_name')");
                $s_id = $conn->insert_id;
            }

            // C. Handle Student
            $check_student = $conn->query("SELECT student_id FROM student WHERE student_LRN = '$lrn'");
            if ($check_student->num_rows > 0) {
                $std_id = $check_student->fetch_assoc()['student_id'];
                $conn->query("UPDATE student SET first_name = '$fname', last_name = '$lname', gender = '$gender' WHERE student_id = '$std_id'");
            } else {
                $conn->query("INSERT INTO student (student_LRN, first_name, last_name, gender) VALUES ('$lrn', '$fname', '$lname', '$gender')");
                $std_id = $conn->insert_id;
            }

            // D. Enrollment
            $check_enroll = $conn->query("SELECT * FROM student_enrollment WHERE student_id = '$std_id' AND section_id = '$s_id' AND academic_year_id = '$ay_id'");
            if ($check_enroll->num_rows == 0) {
                $conn->query("INSERT INTO student_enrollment (student_id, section_id, academic_year_id) VALUES ('$std_id', '$s_id', '$ay_id')");
            }
        }
        fclose($handle);
        $conn->commit();
        header("Location: ../pages/dashboard_principal.php?upload=success");
        exit();
        } catch (Throwable $e) {
            fclose($handle);
            $conn->rollback();
            die("Upload failed: " . $e->getMessage());
        }
    }
}
?>
