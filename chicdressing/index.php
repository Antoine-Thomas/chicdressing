<?php get_header(); ?>

<head>
    <title>Chic Dressing</title>
    <meta name="description" content="Bienvenue chez Chic Dressing.Chic Dressing incarne l'essence même de l'élégance et du style. Notre boutique en ligne offre une expérience shopping exclusive, où chaque visiteur est invité à découvrir un monde de sophistication et de raffinement. Fondée sur le principe de l'unicité, Chic Dressing propose une sélection exquise de vêtements et d'accessoires qui célèbrent l'individualité de chaque personne.
                                      Plongez dans notre collection soigneusement curated où chaque pièce raconte une histoire de luxe et de glamour. De la robe de soirée exquise aux tenues décontractées-chic, notre gamme offre une variété de styles pour toutes les occasions. Avec une attention méticuleuse aux détails et un engagement envers la qualité, Chic Dressing s'efforce de fournir des pièces intemporelles qui transcendent les tendances éphémères.
                                      En plus de notre sélection de vêtements haut de gamme, nous proposons également une gamme d'accessoires de mode qui complètent parfaitement votre tenue. Des sacs à main élégants aux bijoux scintillants, chaque article est choisi pour son esthétique élégante et sa qualité exceptionnelle.
                                      Chez Chic Dressing, nous croyons en l'importance de l'expression personnelle à travers la mode. Notre équipe de stylistes talentueux est là pour vous guider dans la création de looks uniques qui capturent votre individualité et votre style personnel. Avec notre engagement envers le service à la clientèle exceptionnel, nous nous efforçons de rendre chaque expérience d'achat aussi agréable que mémorable.
                                      Découvrez l'ultime destination pour les amateurs de mode exigeants. Avec Chic Dressing, embrassez votre style distinctif et faites une déclaration de mode inoubliable à chaque instant.">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="INDEX, FOLLOW">
    
    <!-- Préchargement des polices -->
    <link href="https://fonts.googleapis.com/css2?family=Kalam&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
	<script src="https://www.google.com/recaptcha/enterprise.js?render=6LecDoEpAAAAAIbNxqXyZwfDbYhGWeD0ZUrcC8cj"></script>
    <!-- Your code -->

</head>

<?php
if ( is_home() ) {

    // Featured Slider, Carousel
    if ( ashe_options( 'featured_slider_label' ) === true && ashe_options( 'featured_slider_location' ) !== 'front' ) {
        if ( ashe_options( 'featured_slider_source' ) === 'posts' ) {
            get_template_part( 'templates/header/featured', 'slider' );
        } else {
            get_template_part( 'templates/header/featured', 'slider-custom' );
        }
    }

    // Featured Links, Banners
    if ( ashe_options( 'featured_links_label' ) === true && ashe_options( 'featured_links_location' ) !== 'front' ) {
        get_template_part( 'templates/header/featured', 'links' );
    }

    // On ajoute les derniers produits
    ?>
    <div id="chic-products"  class="boxed-wrapper clear-fix" id="chic-products">
        <h2 class="chic-title" id="chic-title">Dernières pièces </h2>
        <?php
        echo do_shortcode('[products orderby="date" columns="3" order="ASC"]');
        ?>
        <p class="text-center"><a class="chic-bouton" href="/shop">Voir toute la collection</a></p>
    </div>
    <!-- on inclut la Google Maps de la Fashion Week -->
<div id="chic-fashionweek-map" class="boxed-wrapper clear-fix" style="margin-top:30px" id="chic-fashionweek-map">
    <h3 class="chic-title" id="chic-fashionweek-title">La FashionMap - été 2022 </h3>
    <iframe src="https://www.google.com/maps/d/embed?mid=1SU-W19k76UkTXASeT7PnGAyDYCY&hl=en_US&ehbc=2E312F" width="100%" height="480" title="Carte de la Fashion Week été 2022"></iframe>
</div>

    <?php
}

?>

<div class="main-content clear-fix<?php echo esc_attr(ashe_options( 'general_content_width' )) === 'boxed' ? ' boxed-wrapper': ''; ?>" data-layout="<?php echo esc_attr( ashe_options( 'general_home_layout' ) ); ?>" data-sidebar-sticky="<?php echo esc_attr( ashe_options( 'general_sidebar_sticky' ) ); ?>">

    <?php

    // Sidebar Left
    get_template_part( 'templates/sidebars/sidebar', 'left' );

    // Blog Feed Wrapper

    if ( strpos( ashe_options( 'general_home_layout' ), 'list' ) === 0 ) {
        get_template_part( 'templates/grid/blog', 'list' );
    } else {
        get_template_part( 'templates/grid/blog', 'grid' );
    }

    // Sidebar Right
    get_template_part( 'templates/sidebars/sidebar', 'right' );

    ?>

</div>

<script>
  function onClick(e) {
    e.preventDefault();
    grecaptcha.enterprise.ready(async () => {
      const token = await grecaptcha.enterprise.execute('6LecDoEpAAAAAIbNxqXyZwfDbYhGWeD0ZUrcC8cj', {action: 'LOGIN'});
    });
  }
</script>

<?php get_footer(); ?>
