
        <footer class="footer">
            <div class="footer__col footer__brand">
                <p class="footer__brand-text">
                    <strong>WakAroma</strong>
                    <br>
                    <small>Quand l'Afrique parfume vos instants</small>
                </p>
            </div>

            <div class="footer__col footer__links">
                <h4>Découvrir</h4>
                <ul>
                    <li><a href="#produit">Nos produits</a></li>
                    <li><a href="#historique">Notre historique</a></li>
                    <li><a href="#salon">Nos salons</a></li>
                </ul>
            </div>

            <div class="footer__col footer__contact">
                <h4>Contact</h4>
                <ul>
                    <li>Téléphone : <a href="tel:+33760900621">+33 7 60 90 06 21</a></li>
                    <li>Email : <a href="mailto:contact@wakaroma.example">contact@wakaroma.example</a></li>
                </ul>
            </div>

            <div class="footer__col footer__social">
                <h4>Nous suivre</h4>
                <div class="footer__social-list">
                    <a href="#whatsapp" class="social-link" aria-label="WhatsApp">
                        <img src="logo/whatssapp.png" alt="WhatsApp">
                    </a>
                    <a href="#facebook" class="social-link" aria-label="Facebook">
                        <img src="logo/facebook.png" alt="Facebook">
                    </a>
                    <a href="#instagram" class="social-link" aria-label="Instagram">
                        <img src="logo/instagram.png" alt="Instagram">
                    </a>
                </div>
            </div>

            <div class="footer__bottom">
                <p>&copy; <span id="year"></span> WakAroma — Tous droits réservés.</p>
            </div>
        </footer>

        <script>
            // année automatique
            (function(){
                var y = new Date().getFullYear();
                var el = document.getElementById('year');
                if(el) el.textContent = y;
            })();
        </script>
    </body>
</html>