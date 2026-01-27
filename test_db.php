<?php
/**
 * Script de test de connexion à la base de données
 */

// Configuration WAMP par défaut
$config = [
    'host' => 'localhost',
    'dbname' => 'ila_publications_db',
    'username' => 'root',
    'password' => '', // WAMP par défaut : pas de mot de passe
    'port' => 3306 // Port MySQL par défaut
];

echo "<h2>Test de connexion à la base de données WAMP</h2>";
echo "<pre>";

try {
    // Test 1 : Connexion MySQL simple
    echo "Test 1 : Connexion MySQL...\n";
    $link = mysqli_connect($config['host'], $config['username'], $config['password']);
    
    if ($link) {
        echo "✓ Connexion MySQL réussie\n";
        
        // Test 2 : Vérifier si la base existe
        echo "\nTest 2 : Vérification de la base de données...\n";
        $result = mysqli_query($link, "SHOW DATABASES LIKE '{$config['dbname']}'");
        
        if (mysqli_num_rows($result) > 0) {
            echo "✓ Base de données '{$config['dbname']}' existe\n";
            
            // Test 3 : Vérifier les tables
            mysqli_select_db($link, $config['dbname']);
            echo "\nTest 3 : Vérification des tables...\n";
            
            $tables_result = mysqli_query($link, "SHOW TABLES");
            $tables = [];
            while ($row = mysqli_fetch_array($tables_result)) {
                $tables[] = $row[0];
            }
            
            echo "Tables trouvées : " . implode(', ', $tables) . "\n";
            
            // Test 4 : Vérifier les données dans les tables
            echo "\nTest 4 : Contenu des tables...\n";
            
            foreach ($tables as $table) {
                $count_result = mysqli_query($link, "SELECT COUNT(*) as count FROM `$table`");
                $count_row = mysqli_fetch_assoc($count_result);
                echo "- Table '$table' : {$count_row['count']} enregistrements\n";
            }
            
        } else {
            echo "✗ Base de données '{$config['dbname']}' n'existe pas\n";
            echo "\nPour créer la base de données :\n";
            echo "1. Allez sur phpMyAdmin (http://localhost/phpmyadmin)\n";
            echo "2. Créez une base de données nommée 'ila_publications_db'\n";
            echo "3. Importez vos tables à partir des fichiers JSON\n";
        }
        
        mysqli_close($link);
        
    } else {
        echo "✗ Échec de connexion MySQL : " . mysqli_connect_error() . "\n";
        echo "\nVérifiez que :\n";
        echo "1. WAMP est démarré (icône verte)\n";
        echo "2. Le service MySQL est en cours d'exécution\n";
        echo "3. Le port 3306 est accessible\n";
    }
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

echo "\n\n=== Test PDO ===\n";

// Test avec PDO
try {
    $dsn = "mysql:host={$config['host']};port={$config['port']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connexion PDO réussie (sans base de données)\n";
    
    // Vérifier la base
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['dbname']}'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Base de données accessible via PDO\n";
        
        // Se connecter à la base spécifique
        $pdo_db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo "✓ Connexion à la base '{$config['dbname']}' réussie\n";
        
    } else {
        echo "✗ Base de données non trouvée via PDO\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Erreur PDO : " . $e->getMessage() . "\n";
    echo "\nCode d'erreur : " . $e->getCode() . "\n";
}

echo "</pre>";
?>