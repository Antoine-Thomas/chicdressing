<?php if ( ashe_options('header_image_label') === true ) : ?>
    <div class="entry-header" id="entry-header">
        <div class="cv-outer">
            <div class="cv-inner">
                <div class="header-logo" id="header-logo">
                    <?php if ( has_custom_logo() ) :
                        $custom_logo_id = get_theme_mod( 'custom_logo' );
                        $custom_logo = wp_get_attachment_image_src( $custom_logo_id , 'thumbnail' );
                    ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( bloginfo('name') ); ?>" class="logo-img" id="logo-img">
                            <?php echo esc_attr( bloginfo('name') ); ?>
                        </a>
                        <?php // SEO Hidden Title
                        if ( true === ashe_options( 'title_tagline_seo_title' ) && ( is_home() || is_front_page() || is_category() || is_search() ) ) {
                            echo '<h1 style="display: none;">'.  esc_html( get_bloginfo( 'title' ) ) .'</h1>';
                        }
                        ?>
                    <?php else : ?>
                        <?php if ( is_home() || is_front_page() ) : ?>
                            <h1>
                                <a href="<?php echo esc_url( home_url('/') ); ?>" class="header-logo-a" id="header-logo-a"><?php echo esc_html( bloginfo( 'title' ) ); ?></a>
                            </h1>
                        <?php else : ?>
                            <a href="<?php echo esc_url( home_url('/') ); ?>" class="header-logo-a" id="header-logo-a"><?php echo esc_html( bloginfo( 'title' ) ); ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <p class="site-description" id="site-description"><?php echo esc_html( bloginfo( 'description' ) ); ?></p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>




