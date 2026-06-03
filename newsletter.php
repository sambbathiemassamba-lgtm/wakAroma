<?php
// ==========================================
// TRAITEMENT ABONNEMENT NEWSLETTER (AJAX)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe_newsletter') {
    header('Content-Type: application/json');

    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'wakaroma');

    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $nom   = trim($_POST['nom'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Adresse e-mail invalide.']);
        exit;
    }

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Créer la table si elle n'existe pas
        $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            email       VARCHAR(255) NOT NULL UNIQUE,
            nom         VARCHAR(150) DEFAULT '',
            source      ENUM('newsletter','compte') NOT NULL DEFAULT 'newsletter',
            actif       TINYINT(1) NOT NULL DEFAULT 1,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Vérifier si déjà inscrit
        $stmt = $pdo->prepare("SELECT id, actif FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch(PDO::FETCH_OBJ);

        if ($existing) {
            if ($existing->actif) {
                echo json_encode(['success' => false, 'error' => 'Cette adresse est déjà inscrite à la newsletter.']);
            } else {
                // Réactiver l'abonnement
                $pdo->prepare("UPDATE newsletter_subscribers SET actif = 1, subscribed_at = NOW() WHERE email = ?")
                    ->execute([$email]);
                echo json_encode(['success' => true, 'message' => 'Votre abonnement a été réactivé !']);
            }
            exit;
        }

        $pdo->prepare("INSERT INTO newsletter_subscribers (email, nom, source) VALUES (?, ?, 'newsletter')")
            ->execute([$email, $nom]);

        echo json_encode(['success' => true, 'message' => 'Vous êtes bien inscrit(e) à notre newsletter ✦']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur serveur. Veuillez réessayer.']);
    }
    exit;
}
?>
<!-- Newsletter -->
<section class="newsletter-section">
    <div class="newsletter-inner">
        <div class="newsletter-text">
            <h3 class="newsletter-title">Restez dans la saveur</h3>
            <p class="newsletter-sub">Recevez nos nouveautés, recettes et offres exclusives directement dans votre boîte mail.</p>
        </div>
        <form class="newsletter-form" id="newsletterForm" onsubmit="submitNewsletter(event)">
            <input type="email" id="newsletterEmail" placeholder="votre@email.com" class="newsletter-input" required>
            <button type="submit" class="newsletter-btn" id="newsletterBtn">S'abonner ✦</button>
        </form>
        <div id="newsletter-msg" style="display:none; margin-top:12px; font-size:.88rem; padding: 10px 16px; border-radius:8px;"></div>
    </div>
</section>

<script>
async function submitNewsletter(e) {
    e.preventDefault();
    const email = document.getElementById('newsletterEmail').value.trim();
    const btn   = document.getElementById('newsletterBtn');
    const msg   = document.getElementById('newsletter-msg');

    btn.disabled = true;
    btn.textContent = '…';

    try {
        const body = new URLSearchParams({ action: 'subscribe_newsletter', email });
        const res  = await fetch(window.location.href, { method: 'POST', body });
        const data = await res.json();

        msg.style.display = 'block';
        if (data.success) {
            msg.style.background  = 'rgba(39,174,96,.12)';
            msg.style.border      = '1px solid rgba(39,174,96,.4)';
            msg.style.color       = '#4ecb78';
            msg.textContent       = data.message;
            document.getElementById('newsletterForm').reset();
            btn.textContent = 'Inscrit ✓';
        } else {
            msg.style.background  = 'rgba(192,57,43,.12)';
            msg.style.border      = '1px solid rgba(192,57,43,.4)';
            msg.style.color       = '#e74c3c';
            msg.textContent       = data.error;
            btn.disabled          = false;
            btn.textContent       = "S'abonner ✦";
        }
    } catch {
        msg.style.display     = 'block';
        msg.style.background  = 'rgba(192,57,43,.12)';
        msg.style.border      = '1px solid rgba(192,57,43,.4)';
        msg.style.color       = '#e74c3c';
        msg.textContent       = 'Erreur réseau. Veuillez réessayer.';
        btn.disabled          = false;
        btn.textContent       = "S'abonner ✦";
    }
}
</script>