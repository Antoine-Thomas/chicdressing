<?php

// Ajouter l'action pour enqueuer le CSS de manière asynchrone et précharger d'autres ressources
add_action('wp_head', 'chicdressing_enqueue_async_css_and_preload');

// Fonction pour enqueuer le CSS de manière asynchrone et précharger d'autres ressources
function chicdressing_enqueue_async_css_and_preload() {
    ?>
    <link rel="preload" href="<?php echo get_template_directory_uri() . '/style.css'; ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <script>
        // Fonction pour charger les scripts JavaScript de manière asynchrone
        function loadScript(url) {
            var script = document.createElement('script');
            script.src = url;
            document.head.appendChild(script);
        }

        // Précharger le script JavaScript
        loadScript('<?php echo get_template_directory_uri() . '/js/script.js'; ?>');

        // Précharger l'image
        var imgLink = document.createElement('link');
        imgLink.rel = 'preload';
        imgLink.href = '<?php echo get_template_directory_uri() . '/img/logo.png'; ?>';
        imgLink.as = 'image';
        document.head.appendChild(imgLink);
    </script>
    <?php
}
?>





