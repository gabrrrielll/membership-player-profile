<?php
/**
 * Public Profile Layout Template
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$player_id = isset( $player_id ) ? $player_id : get_the_ID();
$user_id = get_post_meta( $player_id, '_sp_user_id', true );
if ( ! $user_id ) {
    $user_id = get_post_meta( $player_id, 'sp_user', true );
}

// Plugin Settings
$sections = get_option( 'profootball_player_sections', array() );
$allowed_memberships = get_option( 'profootball_allowed_memberships', array() );

// Check Permissions
$can_view_premium = false;
if ( is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    
    // Admin always can view
    if ( current_user_can( 'manage_options' ) ) {
        $can_view_premium = true;
    } else {
        // Check UMP levels
        if ( function_exists( 'ihc_get_user_levels' ) ) {
            $user_levels = ihc_get_user_levels( $current_user_id, true );
            foreach ( (array)$allowed_memberships as $allowed_lid ) {
                if ( in_array( $allowed_lid, $user_levels ) ) {
                    $can_view_premium = true;
                    break;
                }
            }
        }
    }
}

// If no sections defined, show a basic message or standard SP data
?>
<div class="profootball-profile-container">
    
    <!-- Dynamic Navigation Buttons -->
    <?php if ( ! empty( $sections ) ) : ?>
    <nav class="profootball-anchor-nav">
        <?php foreach ( $sections as $index => $section ) : 
            $safe_id = 'section-' . sanitize_title( $section['title'] );
            ?>
            <a href="#<?php echo $safe_id; ?>" class="profootball-anchor-button"><?php echo esc_html( $section['title'] ); ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <!-- Player Header -->
    <div class="profootball-header-card">
        <div class="profootball-header-flex">
            <div class="profootball-main-photo">
                <?php 
                $thumb_url = get_the_post_thumbnail_url( $player_id, 'full' );
                if ( $thumb_url ) : ?>
                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="Player Photo">
                <?php else : ?>
                    <div class="photo-placeholder"><span class="dashicons dashicons-admin-users"></span></div>
                <?php endif; ?>
            </div>
            
            <div class="profootball-header-info">
                <h1 class="player-full-name"><?php echo get_the_title( $player_id ); ?></h1>
                
                <div class="player-meta-row">
                    <?php 
                    // Squad Number (Standard SP)
                    $number = get_post_meta( $player_id, 'sp_number', true );
                    if ( $number ) : ?>
                        <span class="squad-number">#<?php echo esc_html( $number ); ?></span>
                    <?php endif; ?>

                    <?php 
                    // Nationality / Flag logic
                    // We check UMP mapping or SP mapping
                    $nationality = get_user_meta( $user_id, 'nationality', true ); // Default UMP slug guess
                    if ( empty( $nationality ) ) {
                        $nationality = get_post_meta( $player_id, 'sp_nationality', true ); // SP standard
                    }

                    if ( $nationality ) : 
                        // Using a public flag CDN for better visuals
                        $country_code = strtolower( trim( $nationality ) );
                        // If nationality is full name, this might need mapping, but let's assume code for now
                        // For a real app, you'd have a mapping table.
                        ?>
                        <span class="player-nationality">
                            <img src="https://flagcdn.com/w40/<?php echo esc_attr( $country_code ); ?>.png" 
                                 onerror="this.style.display='none'"
                                 alt="<?php echo esc_attr( $nationality ); ?>" 
                                 class="country-flag">
                            <?php echo esc_html( strtoupper( $nationality ) ); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Navigation Buttons (Anchor links) -->
                <?php if ( ! empty( $sections ) ) : ?>
                <nav class="profootball-anchor-nav-inline">
                    <?php foreach ( $sections as $index => $section ) : 
                        $safe_id = 'section-' . sanitize_title( $section['title'] );
                        ?>
                        <a href="#<?php echo $safe_id; ?>" class="profootball-anchor-button mini"><?php echo esc_html( $section['title'] ); ?></a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Render Dynamic Sections -->
    <div class="profootball-content">
        <?php if ( ! empty( $sections ) ) : ?>
            <?php foreach ( $sections as $index => $section ) : 
                $safe_id = 'section-' . sanitize_title( $section['title'] );
                ?>
                <section id="<?php echo $safe_id; ?>" class="profootball-dynamic-section">
                    <div class="section-title-wrap">
                        <h2 class="central-title"><?php echo esc_html( $section['title'] ); ?></h2>
                    </div>

                    <div class="section-body">
                        <?php if ( ! empty( $section['fields'] ) ) : ?>
                            <?php foreach ( $section['fields'] as $field ) : ?>
                                <?php 
                                // Fetch data from UMP mapping
                                $value = '';
                                if ( $user_id ) {
                                    $mapping = ! empty( $field['mapping'] ) ? $field['mapping'] : '';
                                    if ( empty( $mapping ) ) {
                                        $mapping = 'unmapped_field_' . sanitize_title( $field['label'] );
                                        $field['mapping'] = $mapping; // Ensure the render function sees the mapping
                                    }
                                    $value = get_user_meta( $user_id, $mapping, true );
                                }

                                // Handle non-premium users for specific fields
                                if ( ! $can_view_premium && in_array( $field['type'], array( 'file', 'gallery', 'video' ) ) ) {
                                    continue; // Skip premium fields
                                }
                                ?>
                                
                                <div class="profootball-field-item field-type-<?php echo esc_attr( $field['type'] ); ?>">
                                    <span class="field-label"><?php echo esc_html( $field['label'] ); ?></span>
                                    <div class="field-content">
                                        <!-- Debug: User: <?php echo $user_id; ?>, Mapping: <?php echo $mapping; ?> -->
                                        <?php render_profootball_public_field( $field, $value ); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ( ! $can_view_premium ) : ?>
            <div class="profootball-locked-notice">
                <h3>PREMIUM CONTENT LOCKED</h3>
                <p>You need a specific membership to view complete scouting reports, CVs, and video analysis for this player.</p>
                <a href="<?php echo esc_url( home_url( '/membership-plans/' ) ); ?>" class="profootball-anchor-button">View Plans</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Render field content based on type
 */
function render_profootball_public_field( $field, $value ) {
    if ( empty( $value ) ) {
        echo '<span class="empty-field">N/A</span>';
        return;
    }

    $mapping = !empty($field['mapping']) ? $field['mapping'] : '';
    $is_taxonomy = (strpos($mapping, 'tax_') === 0);

    // If it's a taxonomy mapping, $value is likely an array of term IDs
    if ($is_taxonomy) {
        $taxonomy = substr($mapping, 4);
        $term_ids = is_array($value) ? $value : explode(',', $value);
        $term_names = array();
        foreach ($term_ids as $tid) {
            $term = get_term($tid, $taxonomy);
            if ($term && !is_wp_error($term)) {
                $term_names[] = $term->name;
            }
        }
        if (!empty($term_names)) {
            echo esc_html(implode(', ', $term_names));
        } else {
            echo '<span class="empty-field">N/A</span>';
        }
        return;
    }

    switch ( $field['type'] ) {
        case 'textarea':
            echo wp_kses_post( wpautop( $value ) );
            break;
        case 'image':
            $img_url = is_numeric( $value ) ? wp_get_attachment_url( $value ) : $value;
            echo '<img src="' . esc_url( $img_url ) . '" class="profootball-field-image">';
            if ( ! empty( $field['show_download'] ) && $field['show_download'] === '1' ) {
                echo '<div class="profootball-download-wrap"><a href="' . esc_url( $img_url ) . '" class="profootball-download-link secondary" download>Download Image</a></div>';
            }
            break;
        case 'gallery':
            $ids = is_array( $value ) ? $value : explode( ',', $value );
            ?>
            <div class="profootball-gallery-slider">
                <div class="slider-wrapper">
                    <?php foreach ( $ids as $id ) : 
                        $url = is_numeric($id) ? wp_get_attachment_url( trim( $id ) ) : $id;
                        if ( $url ) : ?>
                        <div class="slider-item">
                            <img src="<?php echo esc_url( $url ); ?>">
                        </div>
                    <?php endif; endforeach; ?>
                </div>
                <div class="slider-nav">
                    <button class="slider-prev">&larr;</button>
                    <button class="slider-next">&rarr;</button>
                </div>
            </div>
            <?php
            break;
        case 'video':
            echo wp_oembed_get( $value );
            break;
        case 'file':
            $file_url = is_numeric( $value ) ? wp_get_attachment_url( $value ) : $value;
            $file_name = basename( $file_url );
            if ( ! empty( $field['show_download'] ) && $field['show_download'] === '1' ) {
                echo '<a href="' . esc_url( $file_url ) . '" class="profootball-download-link" target="_blank" download>Download ' . esc_html( $field['label'] ) . ' (' . esc_html( $file_name ) . ')</a>';
            } else {
                echo '<span class="file-status">File Uploaded</span>'; 
            }
            break;
        case 'text':
        default:
            echo esc_html( $value );
            break;
    }
}
