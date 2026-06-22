<?php

require_once '../includes/config.php';

$pdo = getDB();

$all_visits = $pdo->query("
    SELECT vl.*, v.full_name, v.phone, v.photo_path
    FROM visit_logs vl
    JOIN visitors v ON vl.visitor_id = v.id
    ORDER BY vl.check_in DESC
    LIMIT 50
")->fetchAll();

$html = '';

foreach ($all_visits as $v) {

    $html .= '<tr>';

    $html .= '<td>
        <div class="visitor-cell">';

    if ($v['photo_path']) {

        $html .= '<img class="avatar" src="' .
            htmlspecialchars($v['photo_path']) .
            '" alt="">';

    }
    else {

        $html .= '<div class="avatar-placeholder">' .
            strtoupper(substr($v['full_name'],0,1)) .
            '</div>';

    }

    $html .= '
        <div>
            <div class="visitor-name">' .
            htmlspecialchars((string)($v['full_name'] ?? '')) .
            '</div>

            <div class="visitor-phone">' .
            htmlspecialchars((string)($v['phone'] ?? '')) .
            '</div>
        </div>

        </div>

    </td>';

    $html .= '<td>' .
        htmlspecialchars((string)($v['card_number'] ?? '')) .
        '</td>';

    $html .= '<td>' .
        htmlspecialchars((string)($v['host_name'] ?? '')) .
        '</td>';

    $html .= '<td>' .
        htmlspecialchars((string)($v['host_department'] ?? '—')) .
        '</td>';

    $html .= '<td>' .
        htmlspecialchars((string)($v['purpose'] ?? '')) .
        '</td>';

    $html .= '<td>' .
        date('d M Y, h:i A', strtotime($v['check_in'] ?? '')) .
        '</td>';

    $html .= '<td>' .
        ($v['check_out']
            ? date('h:i A', strtotime($v['check_out']))
            : '—') .
        '</td>';

    $html .= '<td>';

    if ($v['status'] === 'checked_in') {

        $html .= '
            <button
                class="checkout-btn"
                onclick="checkoutVisitor('.$v['id'].')">
                Check Out
            </button>

            <span class="badge in">
                ● Inside
            </span>';

    }
    else {

        $html .= '
            <span class="badge out">
                Left
            </span>';

    }

    $html .= '</td>';

    $html .= '</tr>';

}

echo $html;