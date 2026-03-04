<html>
<body>
    <!-- Affichage du message -->
    <p><?= $message ?></p>

    <!-- Redirection vers flattered.com après 5 secondes -->
    <script>
        setTimeout(function() {
            window.location.href = "https://www.flattered.com";
        }, 5000); // 5000 millisecondes = 5 secondes
    </script>
    
    <!-- Si l'utilisateur souhaite être redirigé immédiatement, ajoutez également ce lien -->    
    <p>You are being redirect within 5 seconds.</p>
    <p>If you are not redirected automatically, <a href="https://www.flattered.com">click here</a>.</p>
</body>
</html>