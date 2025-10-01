<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = new Dotenv();
$dotenv->load('.env');

// Configuración de la base de datos
$host = 'plusvita-db-1';
$dbname = 'myDb';
$username = 'vitaplus';
$password = '1ajd2OnnauhGfvlURTg2';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado a la base de datos\n";
    
    // Obtener todos los doctores con sus modalidades
    $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, username, password, telefono, legajo, modalidad, habilitado FROM doctor WHERE modalidad IS NOT NULL AND modalidad != '[]'");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontrados " . count($doctors) . " doctores para migrar\n";
    
    $migrated = 0;
    $errors = 0;
    
    foreach ($doctors as $doctor) {
        try {
            // Verificar si ya existe un usuario con este email
            $checkStmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
            $checkStmt->execute([$doctor['email']]);
            
            if ($checkStmt->fetch()) {
                echo "Usuario con email {$doctor['email']} ya existe, saltando...\n";
                continue;
            }
            
            // Generar username único si no tiene
            $username = $doctor['username'] ?: strtolower($doctor['nombre'] . '_' . $doctor['apellido']);
            $originalUsername = $username;
            $counter = 1;
            
            while (true) {
                $checkUsername = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                $checkUsername->execute([$username]);
                if (!$checkUsername->fetch()) {
                    break;
                }
                $username = $originalUsername . '_' . $counter;
                $counter++;
            }
            
            // Insertar usuario
            $insertStmt = $pdo->prepare("
                INSERT INTO user (email, username, password, habilitado, nombre, apellido, telefono, legajo, legacy_roles, modalidad) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insertStmt->execute([
                $doctor['email'],
                $username,
                $doctor['password'],
                $doctor['habilitado'],
                $doctor['nombre'],
                $doctor['apellido'],
                $doctor['telefono'],
                $doctor['legajo'],
                '["ROLE_STAFF"]',
                $doctor['modalidad']
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Asignar rol doctor
            $roleStmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, (SELECT id FROM roles WHERE name = 'doctor'))");
            $roleStmt->execute([$userId]);
            
            echo "✓ Migrado: {$doctor['nombre']} {$doctor['apellido']} ({$doctor['email']}) - Modalidad: {$doctor['modalidad']}\n";
            $migrated++;
            
        } catch (Exception $e) {
            echo "✗ Error migrando {$doctor['nombre']} {$doctor['apellido']}: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "Migrados exitosamente: $migrated\n";
    echo "Errores: $errors\n";
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
