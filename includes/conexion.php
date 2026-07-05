<?php
/**
 * conexion.php - Conexión a la base de datos con PDO
 */
// Funcion conectarse a la base de datos y devolver la instancia de PDO
function conectarBD(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        // Configuración de la base de datos (ajusta si es necesario)
        $host = 'localhost';
        $dbname = 'gestor_archivos'; 
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}