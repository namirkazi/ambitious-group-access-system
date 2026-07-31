<?php

function generateAttendanceReport(PDO $pdo, $from, $to, $search = '')
{
    // -----------------------------
    // Load Employees
    // -----------------------------

    $sql = "
        SELECT
            id,
            title,
            full_name,
            department,
            designation,
            photo_path
        FROM employees
        WHERE active = 1
    ";

    $params = [];

    if ($search != '') {

        $sql .= "
            AND (
                full_name LIKE ?
                OR department LIKE ?
                OR designation LIKE ?
            )
        ";

        $like = "%{$search}%";

        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY full_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -----------------------------
    // Load Attendance Records
    // -----------------------------

    $stmt = $pdo->prepare("
        SELECT
            employee_id,
            attendance_date,
            check_in,
            check_out,
            total_hours
        FROM employee_attendance
        WHERE DATE(attendance_date)
        BETWEEN ? AND ?
    ");

    $stmt->execute([$from, $to]);

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -----------------------------
    // Index Attendance
    // -----------------------------

    $attendanceIndex = [];

    foreach ($records as $record) {

        $date = date(
            'Y-m-d',
            strtotime($record['attendance_date'])
        );

        $attendanceIndex[$date][$record['employee_id']] = $record;
    }

    // -----------------------------
    // Generate Report
    // -----------------------------

    $attendance = [];

    $period = new DatePeriod(

        new DateTime($from),

        new DateInterval('P1D'),

        (new DateTime($to))->modify('+1 day')

    );

    foreach ($period as $day) {

        // Skip Sundays

        if ($day->format('w') == 0) {
            continue;
        }

        $date = $day->format('Y-m-d');

        foreach ($employees as $employee) {

            if (isset($attendanceIndex[$date][$employee['id']])) {

                $record = $attendanceIndex[$date][$employee['id']];

                if ($record['check_out']) {

                    $status = 'Checked Out';

                } elseif ($date == date('Y-m-d')) {

                    $status = 'Present';

                } else {

                    $status = 'Missed Checkout';

                }

                $attendance[] = [

                    'id' => $employee['id'],
                    'title' => $employee['title'],
                    'full_name' => $employee['full_name'],
                    'department' => $employee['department'],
                    'designation' => $employee['designation'],
                    'photo_path' => $employee['photo_path'],

                    'attendance_date' => $date,

                    'check_in' => $record['check_in'],
                    'check_out' => $record['check_out'],
                    'total_hours' => $record['total_hours'],

                    'status' => $status

                ];

            } else {

                $attendance[] = [

                    'id' => $employee['id'],
                    'title' => $employee['title'],
                    'full_name' => $employee['full_name'],
                    'department' => $employee['department'],
                    'designation' => $employee['designation'],
                    'photo_path' => $employee['photo_path'],

                    'attendance_date' => $date,

                    'check_in' => null,
                    'check_out' => null,
                    'total_hours' => null,

                    'status' => 'Absent'

                ];

            }

        }

    }

    return $attendance;
}