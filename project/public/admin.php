<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../../api/profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
</head>
<body>
     <header class="topbar">
        <div class="topbar-inner">

            <div class="topbar-left">
                
                <a href="/PAP/project/public/index"><img class="logo" src="../assets/aemtg.jpg" alt="Logo", ></a>
            </div>

            <div class="topbar-center">
                <a href="/PAP/project/public/index.php">Principal</a>
                <a href="/PAP/api/profile.php">Pagina pessoal</a>
                <a href="/PAP/project/public/dashboard">Horario</a>
            </div>

            <div class="topbar-right">
                <h1>Aluno</h1>
            </div>

        </div>
  </header>
  <main>
    <h1>Admin blyat</h1>
    <form id="saveLoginForm">
        <input type="text" name="login" placeholder="Login" required>
        <input type="text" name="password" placeholder="Password">
        <input type="text" name="uid" placeholder="UID" required>

        <select name="role" required>
            <option value="">Select role</option>
            <option value="Aluno">Aluno</option>
            <option value="Professor">Professor</option>
            <option value="admin">admin</option>
        </select>

        <button type="submit">Add / Update</button>
    </form>
</main>
</body>

</html>





<script>
document.getElementById('saveLoginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  const response = await fetch('/PAP/api/save_login.php', {
    method: 'POST',
    body: formData
  });

  const result = await response.json();
  alert(JSON.stringify(result));
});
</script>