<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/functions_adm.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    if (updateAdminContactInfo($email, $phone)) {
        $_SESSION['success_message'] = 'Контактная информация обновлена';
        redirect('/admin/cab.php');
    } else {
        $_SESSION['error_message'] = 'Ошибка при обновлении контактной информации';
    }
}

$admin_info = getAdminContactInfo();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Волшебная ЛАВКА</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <main class="admin-cab">
        <h1>Личный кабинет администратора</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="notification success"><?php echo $_SESSION['success_message']; ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="notification error"><?php echo $_SESSION['error_message']; ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <form method="POST" class="admin-form">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($admin_info['email']) ? sanitize($admin_info['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo isset($admin_info['phone']) ? sanitize($admin_info['phone']) : ''; ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </form>
    </main>
    
    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
