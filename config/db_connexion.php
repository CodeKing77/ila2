<?php
/**
 * Connexion à la base de données ILA Publications
 */
class Database {
    private static $instance = null;
    private $connection;
    
    // Configuration de la base de données
    private $host = 'localhost';
    private $dbname = 'ila_publications_db';
    private $username = 'root'; // Utilisateur par défaut WAMP
    private $password = ''; // Mot de passe par défaut WAMP (vide)
    private $charset = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            // En production, loguer l'erreur sans afficher les détails
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Méthode utilitaire pour exécuter une requête
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

// Fonction utilitaire pour obtenir une connexion
function getDB() {
    return Database::getInstance()->getConnection();
}

// Test de connexion (à commenter en production)
/*
try {
    $db = getDB();
    echo "Connexion à la base de données réussie!";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
*/
?>