<?php
/**
 * ARCHIVO: api_usuarios.php
 */
require_once 'db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') exit;

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode($pdo->query("SELECT id, nombre, email as usuario, rol FROM usuarios ORDER BY nombre ASC")->fetchAll());
} elseif ($action === 'delete') {
    $id = $_POST['id'];
    if($id != $_SESSION['user_id']) $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
} elseif ($action === 'save') {
    $id = $_POST['id']; $nom = trim($_POST['nombre']); $mail = trim($_POST['usuario']); $pass = trim($_POST['clave']); $rol = $_POST['rol'];
    if(!empty($id)) {
        if(!empty($pass)) $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, password=?, rol=? WHERE id=?")->execute([$nom, $mail, password_hash($pass, PASSWORD_DEFAULT), $rol, $id]);
        else $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id=?")->execute([$nom, $mail, $rol, $id]);
    } else {
        $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?,?,?,?)")->execute([$nom, $mail, password_hash($pass, PASSWORD_DEFAULT), $rol]);
    }
    echo json_encode(['success' => true]);
}