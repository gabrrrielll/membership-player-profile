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
    
    <!-- Navigation Buttons will now be handled as a positionable field from admin -->


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
                            <img src="https://flagcdn.com/w160/<?php echo esc_attr( $country_code ); ?>.png" 
                                 onerror="this.style.display='none'"
                                 alt="<?php echo esc_attr( $nationality ); ?>" 
                                 class="country-flag">
                            <?php echo esc_html( strtoupper( $nationality ) ); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Navigation mini-buttons can be kept or removed based on preference. 
                     Keeping current logic for header mini-nav if sections exist. -->
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
                        <div class="profootball-grid-row">
                            <?php 
                            if ( ! empty( $section['fields'] ) ) : 
                                // Prepare fields to ensure we have sequential iteration but preserve original keys
                                $fields_list = array();
                                foreach ( $section['fields'] as $key => $f ) {
                                    $f['_abs_idx'] = $key;
                                    $fields_list[] = $f;
                                }
                                
                                $total_fields = count($fields_list);
                                $i = 0;

                                while ($i < $total_fields) :
                                    $field = $fields_list[$i];
                                    $col_width = ! empty( $field['width'] ) ? $field['width'] : '12';
                                    $css_id = ! empty( $field['css_id'] ) ? $field['css_id'] : '';
                                    $css_class = ! empty( $field['css_class'] ) ? $field['css_class'] : '';

                                    // Collect grouped fields
                                    $sub_fields = array($field);
                                    $next_idx = $i + 1;
                                    while ($next_idx < $total_fields && ! empty($fields_list[$next_idx]['is_grouped']) && $fields_list[$next_idx]['is_grouped'] === '1') {
                                        $sub_fields[] = $fields_list[$next_idx];
                                        $next_idx++;
                                    }
                                    
                                    // Update main loop index
                                    $i = $next_idx;
                                    ?>
                                    
                                    <div <?php echo $css_id ? 'id="'.esc_attr($css_id).'"' : ''; ?> class="profootball-grid-col col-<?php echo esc_attr($col_width); ?> profootball-field-item-group <?php echo esc_attr($css_class); ?>">
                                        <?php foreach ($sub_fields as $s_idx => $s_field) : ?>
                                            <?php 
                                            // Fetch data
                                            $abs_idx = $s_field['_abs_idx'];
                                            $value = '';
                                            if ( $user_id ) {
                                                $mapping = ! empty( $s_field['mapping'] ) ? $s_field['mapping'] : '';
                                                if ( empty( $mapping ) ) {
                                                    $mapping_suffix = ! empty( $s_field['label'] ) ? sanitize_title( $s_field['label'] ) : $abs_idx;
                                                    $mapping = 'unmapped_field_' . $mapping_suffix;
                                                }
                                                $value = get_user_meta( $user_id, $mapping, true );
                                            }

                                            // Skip premium fields for non-premium users
                                            if ( ! $can_view_premium && in_array( $s_field['type'], array( 'file', 'gallery', 'video' ) ) ) {
                                                continue;
                                            }

                                            // Skip empty fields to avoid "N/A" blocks in the layout
                                            if ( empty( $value ) && ! in_array( $s_field['type'], array( 'shortcut_buttons', 'empty_space' ) ) ) {
                                                continue;
                                            }

                                            $s_css_class = ! empty( $s_field['css_class'] ) ? $s_field['css_class'] : '';
                                            if ( ! empty( $s_field['label_pos'] ) && $s_field['label_pos'] === 'left' ) {
                                                $s_css_class .= ' profootball-label-left';
                                            }
                                            ?>
                                            <div class="profootball-field-item field-type-<?php echo esc_attr( $s_field['type'] ); ?> <?php echo ($s_idx === 0) ? '' : esc_attr($s_css_class); ?>">
                                                <?php if ( ! empty( $s_field['label'] ) && ! in_array( $s_field['type'], array( 'empty_space', 'shortcut_buttons' ) ) ) : ?>
                                                    <span class="field-label"><?php echo esc_html( $s_field['label'] ); ?></span>
                                                <?php endif; ?>

                                                <?php if ( $s_field['type'] !== 'empty_space' ) : ?>
                                                    <div class="field-content">
                                                        <?php render_profootball_public_field( $s_field, $value ); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
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
function profootball_build_options_map( $raw_options ) {
    $map = array();

    if ( empty( $raw_options ) ) {
        return $map;
    }

    if ( is_array( $raw_options ) ) {
        foreach ( $raw_options as $key => $label ) {
            if ( is_array( $label ) ) {
                if ( isset( $label['value'] ) ) {
                    $value = (string) $label['value'];
                    $label_text = isset( $label['label'] ) ? (string) $label['label'] : $value;
                    $map[ $value ] = $label_text;
                }
                continue;
            }
            if ( is_int( $key ) ) {
                $value = (string) $label;
                $label_text = (string) $label;
            } else {
                $value = (string) $key;
                $label_text = (string) $label;
            }
            if ( $value === '' ) {
                continue;
            }
            $map[ $value ] = $label_text;
        }
        return $map;
    }

    $raw_options = is_string( $raw_options ) ? trim( $raw_options ) : '';
    if ( $raw_options === '' ) {
        return $map;
    }

    $lines = preg_split( '/\r\n|\r|\n/', $raw_options );
    if ( count( $lines ) === 1 ) {
        $lines = explode( ',', $raw_options );
    }

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( $line === '' ) {
            continue;
        }
        $parts = array_map( 'trim', explode( '|', $line, 2 ) );
        $value = (string) $parts[0];
        $label_text = isset( $parts[1] ) && $parts[1] !== '' ? (string) $parts[1] : $value;
        $map[ $value ] = $label_text;
    }

    return $map;
}

function render_profootball_public_field( $field, $value ) {
    if ( empty( $value ) && $field['type'] !== 'shortcut_buttons' ) {
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

    $options_map = ! empty( $field['options'] ) ? profootball_build_options_map( $field['options'] ) : array();

    if ( $field['type'] === 'multiselect' || is_array( $value ) ) {
        $values_list = is_array( $value ) ? $value : preg_split( '/\s*,\s*/', (string) $value );
        $values_list = array_filter( array_map( 'trim', $values_list ), 'strlen' );
        if ( empty( $values_list ) ) {
            echo '<span class="empty-field">N/A</span>';
            return;
        }
        echo '<ul class="profootball-multiselect-values">';
        foreach ( $values_list as $item ) {
            $key = (string) $item;
            $label_text = isset( $options_map[ $key ] ) ? $options_map[ $key ] : $item;
            echo '<li>' . esc_html( $label_text ) . '</li>';
        }
        echo '</ul>';
        return;
    }

    if ( $field['type'] === 'select' && ! empty( $options_map ) ) {
        $value_key = (string) $value;
        if ( isset( $options_map[ $value_key ] ) ) {
            $value = $options_map[ $value_key ];
        }
    }

    switch ( $field['type'] ) {
        case 'textarea':
            echo wp_kses_post( wpautop( $value ) );
            break;
        case 'image':
            $img_url = is_numeric( $value ) ? wp_get_attachment_url( $value ) : $value;
            echo '<img src="' . esc_url( $img_url ) . '" class="profootball-field-image">';
            if ( ! empty( $field['show_download'] ) && $field['show_download'] === '1' ) {
                $btn_text = ! empty( $field['download_text'] ) ? $field['download_text'] : 'Download Image';
                echo '<div class="profootball-download-wrap"><a href="' . esc_url( $img_url ) . '" class="profootball-download-link secondary" download>' . esc_html( $btn_text ) . '</a></div>';
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
            $width = isset( $field['video_width'] ) ? $field['video_width'] : '';
            $height = isset( $field['video_height'] ) ? $field['video_height'] : '';
            
            // Cleanup input
            $width = trim( $width );
            $height = trim( $height );
            
            // Check for "auto" height which implies responsive 16:9
            $is_responsive = ( strtolower( $height ) === 'auto' );
            
            $oembed_args = array();
            
            // If explicit numeric width/height, pass to oEmbed
            if ( ! empty( $width ) && is_numeric( $width ) ) {
                $oembed_args['width'] = $width;
            }
            if ( ! empty( $height ) && is_numeric( $height ) ) {
                $oembed_args['height'] = $height;
            }
            
            // Get the raw embed HTML
            $html = wp_oembed_get( $value, $oembed_args );
            
            if ( $html ) {
                if ( $is_responsive ) {
                    // Responsive wrapper for 16:9 Aspect Ratio
                    echo '<div class="profootball-video-responsive" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;">';
                    // Force the iframe to fill the container absolute
                    // Note: Some simple string replacement to ensure inline styles work
                    $html = str_replace( '<iframe', '<iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"', $html );
                    echo $html;
                    echo '</div>';
                } else {
                    // Normal display, but respect width if it's a percentage (e.g. 100%)
                    $container_style = '';
                    if ( ! empty( $width ) && ! is_numeric( $width ) ) {
                        $container_style = 'style="width:' . esc_attr( $width ) . ';"';
                    }
                    echo '<div class="profootball-video-wrap" ' . $container_style . '>' . $html . '</div>';
                }
            } else {
                // Fallback for non-oembed links? Link to video
                echo '<a href="' . esc_url( $value ) . '" target="_blank">Watch Video</a>';
            }
            break;
        case 'nationality':
            $country_code = strtolower( trim( $value ) );
            $custom_width = ! empty( $field['options'] ) ? trim( $field['options'] ) : '40px';
            $show_nat_name = isset( $field['show_nat_name'] ) ? $field['show_nat_name'] : '1';

            // Ensure width has a unit if it's just a number
            if ( is_numeric( $custom_width ) ) {
                $custom_width .= 'px';
            }
            // Use w320 for high resolution, then scale down with CSS width
            echo '<div class="player-nationality dynamic">';
            echo '<img src="https://flagcdn.com/w320/' . esc_attr( $country_code ) . '.png" 
                       onerror="this.style.display=\'none\'" 
                       class="country-flag" 
                       style="width:' . esc_attr( $custom_width ) . '; height:auto; vertical-align:middle;">';
            if ( $show_nat_name === '1' ) {
                echo ' ' . esc_html( strtoupper( $value ) );
            }
            echo '</div>';
            break;
        case 'file':
            $file_url = is_numeric( $value ) ? wp_get_attachment_url( $value ) : $value;
            $file_name = basename( $file_url );
            if ( ! empty( $field['show_download'] ) && $field['show_download'] === '1' ) {
                $btn_text = ! empty( $field['download_text'] ) ? $field['download_text'] : 'Download ' . $field['label'];
                echo '<a href="' . esc_url( $file_url ) . '" class="profootball-download-link" target="_blank" download>' . esc_html( $btn_text ) . ' (' . esc_html( $file_name ) . ')</a>';
            } else {
                echo '<span class="file-status">File Uploaded</span>'; 
            }
            break;
        case 'shortcut_buttons':
            $sections = get_option( 'profootball_player_sections', array() );
            if ( ! empty( $sections ) ) {
                echo '<nav class="profootball-anchor-nav">';
                foreach ( $sections as $s ) {
                    $safe_id = 'section-' . sanitize_title( $s['title'] );
                    echo '<a href="#' . $safe_id . '" class="profootball-anchor-button">' . esc_html( $s['title'] ) . '</a>';
                }
                echo '</nav>';
            }
            break;
        case 'text':
        default:
            echo esc_html( $value );
            break;
    }
}
