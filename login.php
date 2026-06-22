<?php
session_start();
require_once 'includes/config.php';

if(isset($_SESSION['admin_logged_in'])){

    if($_SESSION['role']=='hr'){

        header("Location: attendance.php");

    }
    else{

        header("Location: admin.php");

    }

    exit();

}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {

        $pdo = getDB();

        $stmt = $pdo->prepare(
            "SELECT * FROM admin_users WHERE username = ? LIMIT 1"
        );

        $stmt->execute([$username]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if($user['role']=='hr'){

    header("Location: attendance.php");

}
else{

    header("Location: admin.php");

}

exit();

        } else {

            $error = "Invalid username or password";

        }

    } catch (Exception $e) {

        $error = "Login failed";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ambitious Group Login</title>
<link rel="apple-touch-icon"
sizes="180x180"
href="assets/favicon/apple-touch-icon.png">

<link rel="icon"
type="image/png"
sizes="32x32"
href="assets/favicon/favicon-32x32.png">

<link rel="icon"
type="image/png"
sizes="16x16"
href="assets/favicon/favicon-16x16.png">

<link rel="icon"
href="assets/favicon/favicon.ico">

<link rel="manifest"
href="assets/favicon/site.webmanifest">
<style>
:root{
    --bg:#0f1117;
    --surface:#1a1d27;
    --border:#2d3148;
    --accent:#6c63ff;
    --accent2:#a78bfa;
    --text:#e2e8f0;
    --muted:#94a3b8;
    --danger:#ef4444;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:var(--bg);
    color:var(--text);
    font-family:'Segoe UI',sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.card{
    width:100%;
    max-width:400px;
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:16px;
    padding:2rem;
}

h1{
    text-align:center;
    margin-bottom:1.5rem;
}

h1 span{
    color:var(--accent2);
}

.form-group{
    margin-bottom:1rem;
}

label{
    display:block;
    margin-bottom:.5rem;
    color:var(--muted);
}

input{
    width:100%;
    padding:.85rem 1rem;
    background:#222538;
    border:1px solid var(--border);
    border-radius:10px;
    color:white;
    font-size:.95rem;
}

input:focus{
    outline:none;
    border-color:var(--accent);
}

button{
    width:100%;
    padding:.9rem;
    border:none;
    border-radius:10px;
    background:linear-gradient(
        135deg,
        var(--accent),
        var(--accent2)
    );
    color:white;
    font-size:.95rem;
    font-weight:700;
    cursor:pointer;
}

button:hover{
    opacity:.9;
}

.error{
    background:rgba(239,68,68,.15);
    color:#fca5a5;
    padding:.8rem;
    border-radius:10px;
    margin-bottom:1rem;
}
</style>
</head>
<body>

<div class="card">

<h1>Ambitious<span>Group</span></h1>

<?php if($error): ?>
<div class="error">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST">

    <div class="form-group">
        <label>Username</label>
        <input
            type="text"
            name="username"
            required>
    </div>

    <div class="form-group">
        <label>Password</label>
        <input
            type="password"
            name="password"
            required>
    </div>

    <button type="submit">
        Login
    </button>

</form>

</div>

</body>
</html>