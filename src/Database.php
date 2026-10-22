<?php
// src/Database.php

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';

            $portPart = !empty($config['port']) ? ";port={$config['port']}" : '';
            $dsn = "mysql:host={$config['host']}{$portPart};dbname={$config['dbname']};charset={$config['charset']}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Configuration SSL pour les bases distantes (TiDB, Aiven, PlanetScale, etc.)
            if (!empty($config['ssl_ca']) && file_exists($config['ssl_ca'])) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
            } elseif (!empty($config['ssl'])) {
                // Active le chiffrement SSL
                if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $e) {
                self::renderConnectionError($e, $config);
            }
        }

        return self::$instance;
    }

    private static function renderConnectionError(PDOException $e, array $config): never
    {
        $host = $config['host'];
        $isLocalhost = in_array($host, ['localhost', '127.0.0.1']);

        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erreur Base de Données - TaskFlow</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
                .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 30px; max-width: 600px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
                h1 { margin-top: 0; color: #f43f5e; font-size: 1.4rem; display: flex; align-items: center; gap: 10px; }
                p { line-height: 1.6; color: #cbd5e1; font-size: 0.95rem; }
                .details { background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #334155; margin: 20px 0; font-family: monospace; font-size: 0.85rem; color: #94a3b8; }
                .details div { margin-bottom: 6px; }
                .details strong { color: #38bdf8; }
                .help-box { background: rgba(56, 189, 248, 0.1); border-left: 4px solid #38bdf8; padding: 15px; border-radius: 0 8px 8px 0; margin-top: 20px; font-size: 0.9rem; }
                .help-box h3 { margin: 0 0 8px 0; color: #38bdf8; font-size: 1rem; }
                .help-box ol { margin: 0; padding-left: 20px; }
                .help-box li { margin-bottom: 6px; color: #cbd5e1; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>⚠️ Erreur de connexion MySQL</h1>
                <p>TaskFlow n'a pas pu se connecter à la base de données configurée.</p>
                
                <div class="details">
                    <div><strong>Message :</strong> <?= htmlspecialchars($e->getMessage()) ?></div>
                    <div><strong>Hôte (DB_HOST) :</strong> <?= htmlspecialchars($config['host']) ?></div>
                    <div><strong>Port (DB_PORT) :</strong> <?= htmlspecialchars((string)$config['port']) ?></div>
                    <div><strong>Base (DB_NAME) :</strong> <?= htmlspecialchars($config['dbname']) ?></div>
                    <div><strong>Utilisateur (DB_USER) :</strong> <?= htmlspecialchars($config['username']) ?></div>
                    <div><strong>SSL activé (DB_SSL) :</strong> <?= $config['ssl'] ? 'Oui' : 'Non' ?></div>
                </div>

                <div class="help-box">
                    <h3>💡 Comment résoudre cette erreur ?</h3>
                    <?php if ($isLocalhost): ?>
                        <p style="margin-top:0; color:#f8fafc;">
                            <strong>Le serveur tente de se connecter à "localhost" :</strong> Vercel ne peut pas accéder au XAMPP de votre PC personnel.
                        </p>
                        <ol>
                            <li>Créez une base MySQL Cloud gratuite sur <strong><a href="https://tidbcloud.com" target="_blank" style="color:#38bdf8;">tidbcloud.com</a></strong> (ou Aiven/Clever Cloud).</li>
                            <li>Sur Vercel, allez dans <strong>Settings &gt; Environment Variables</strong> et renseignez <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASSWORD</code>, <code>DB_SSL=true</code>.</li>
                            <li><strong>Important :</strong> Après avoir ajouté ou modifié les variables sur Vercel, allez dans l'onglet <strong>Deployments</strong>, cliquez sur les <code>...</code> du dernier déploiement puis sur <strong>Redeploy</strong>.</li>
                        </ol>
                    <?php else: ?>
                        <ol>
                            <li>Vérifiez l'hôte (<code><?= htmlspecialchars($config['host']) ?></code>) et le port (<code><?= htmlspecialchars((string)$config['port']) ?></code>).</li>
                            <li>Vérifiez l'utilisateur et le mot de passe.</li>
                            <li>Vérifiez que votre base Cloud autorise les connexions distantes et activez le SSL (<code>DB_SSL=true</code>).</li>
                            <li>Pensez à <strong>Redeploy</strong> sur Vercel pour appliquer les changements.</li>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}