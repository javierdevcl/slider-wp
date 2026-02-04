<?php
/**
 * Plugin Name: Owl Carousel Slider
 * Description: Agrega un slider con Owl Carousel usando un Custom Post Type con orden personalizado.
 * Version: 2.0
 * Author: Tu Nombre
 * Text Domain: owl-slider
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// Constantes del plugin
define('OWL_SLIDER_VERSION', '2.0');
define('OWL_SLIDER_PATH', plugin_dir_path(__FILE__));
define('OWL_SLIDER_URL', plugin_dir_url(__FILE__));

/**
 * Registrar Custom Post Type para los Slides
 */
function owl_slider_register_post_type() {
    $labels = array(
        'name'               => __('Slides', 'owl-slider'),
        'singular_name'      => __('Slide', 'owl-slider'),
        'add_new'            => __('Agregar Nuevo', 'owl-slider'),
        'add_new_item'       => __('Agregar Nuevo Slide', 'owl-slider'),
        'edit_item'          => __('Editar Slide', 'owl-slider'),
        'new_item'           => __('Nuevo Slide', 'owl-slider'),
        'view_item'          => __('Ver Slide', 'owl-slider'),
        'search_items'       => __('Buscar Slides', 'owl-slider'),
        'not_found'          => __('No se encontraron slides', 'owl-slider'),
        'not_found_in_trash' => __('No hay slides en la papelera', 'owl-slider'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'supports'            => array('title'),
        'menu_icon'           => 'dashicons-images-alt2',
        'rewrite'             => false,
    );
    register_post_type('owl_slides', $args);
}
add_action('init', 'owl_slider_register_post_type');

/**
 * Agregar Metabox para Imágenes, Enlace y Orden
 */
function owl_slider_add_meta_boxes() {
    add_meta_box(
        'owl_slider_meta',
        __('Detalles del Slide', 'owl-slider'),
        'owl_slider_meta_callback',
        'owl_slides',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'owl_slider_add_meta_boxes');

/**
 * Callback del Metabox
 */
function owl_slider_meta_callback($post) {
    // Nonce para seguridad
    wp_nonce_field('owl_slider_save_meta', 'owl_slider_nonce');

    $image_pc = get_post_meta($post->ID, '_owl_slider_image_pc', true);
    $image_mobile = get_post_meta($post->ID, '_owl_slider_image_mobile', true);
    $slide_link = get_post_meta($post->ID, '_owl_slider_link', true);
    $slide_order = get_post_meta($post->ID, '_owl_slider_order', true);
    ?>
    <style>
        .owl-slider-field { margin-bottom: 20px; }
        .owl-slider-field label { display: block; font-weight: 600; margin-bottom: 5px; }
        .owl-slider-field input[type="text"],
        .owl-slider-field input[type="url"],
        .owl-slider-field input[type="number"] { width: 100%; max-width: 500px; }
        .owl-slider-preview { margin-top: 10px; max-width: 300px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; display: none; }
        .owl-slider-preview.has-image { display: block; }
        .owl-slider-image-wrap { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .owl-slider-image-wrap input { flex: 1; min-width: 200px; }
    </style>

    <div class="owl-slider-field">
        <label for="owl_image_pc"><?php _e('Imagen para PC:', 'owl-slider'); ?></label>
        <div class="owl-slider-image-wrap">
            <input type="text" id="owl_image_pc" name="owl_image_pc" value="<?php echo esc_attr($image_pc); ?>" />
            <button type="button" class="owl-upload-button button" data-target="owl_image_pc"><?php _e('Seleccionar', 'owl-slider'); ?></button>
            <button type="button" class="owl-remove-button button" data-target="owl_image_pc"><?php _e('Quitar', 'owl-slider'); ?></button>
        </div>
        <img src="<?php echo esc_url($image_pc); ?>" class="owl-slider-preview <?php echo $image_pc ? 'has-image' : ''; ?>" id="owl_image_pc_preview" alt="">
    </div>

    <div class="owl-slider-field">
        <label for="owl_image_mobile"><?php _e('Imagen para Móvil:', 'owl-slider'); ?></label>
        <div class="owl-slider-image-wrap">
            <input type="text" id="owl_image_mobile" name="owl_image_mobile" value="<?php echo esc_attr($image_mobile); ?>" />
            <button type="button" class="owl-upload-button button" data-target="owl_image_mobile"><?php _e('Seleccionar', 'owl-slider'); ?></button>
            <button type="button" class="owl-remove-button button" data-target="owl_image_mobile"><?php _e('Quitar', 'owl-slider'); ?></button>
        </div>
        <img src="<?php echo esc_url($image_mobile); ?>" class="owl-slider-preview <?php echo $image_mobile ? 'has-image' : ''; ?>" id="owl_image_mobile_preview" alt="">
    </div>

    <div class="owl-slider-field">
        <label for="owl_slide_link"><?php _e('Enlace (URL):', 'owl-slider'); ?></label>
        <input type="url" id="owl_slide_link" name="owl_slide_link" value="<?php echo esc_url($slide_link); ?>" placeholder="https://" />
    </div>

    <div class="owl-slider-field">
        <label for="owl_slide_order"><?php _e('Orden:', 'owl-slider'); ?></label>
        <input type="number" id="owl_slide_order" name="owl_slide_order" value="<?php echo esc_attr($slide_order); ?>" min="0" step="1" style="width: 100px;" />
        <p class="description"><?php _e('Número menor = aparece primero', 'owl-slider'); ?></p>
    </div>
    <?php
}

/**
 * Encolar scripts del admin para media uploader
 */
function owl_slider_admin_scripts($hook) {
    global $post_type;

    if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'owl_slides') {
        wp_enqueue_media();
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Upload button
                $(".owl-upload-button").on("click", function(e) {
                    e.preventDefault();
                    var targetId = $(this).data("target");
                    var input = $("#" + targetId);
                    var preview = $("#" + targetId + "_preview");

                    var frame = wp.media({
                        title: "' . esc_js(__('Seleccionar Imagen', 'owl-slider')) . '",
                        button: { text: "' . esc_js(__('Usar esta imagen', 'owl-slider')) . '" },
                        multiple: false
                    });

                    frame.on("select", function() {
                        var attachment = frame.state().get("selection").first().toJSON();
                        input.val(attachment.url);
                        preview.attr("src", attachment.url).addClass("has-image");
                    });

                    frame.open();
                });

                // Remove button
                $(".owl-remove-button").on("click", function(e) {
                    e.preventDefault();
                    var targetId = $(this).data("target");
                    $("#" + targetId).val("");
                    $("#" + targetId + "_preview").attr("src", "").removeClass("has-image");
                });
            });
        ');
    }
}
add_action('admin_enqueue_scripts', 'owl_slider_admin_scripts');

/**
 * Guardar Metabox Data con seguridad
 */
function owl_slider_save_meta_data($post_id) {
    // Verificar autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verificar nonce
    if (!isset($_POST['owl_slider_nonce']) || !wp_verify_nonce($_POST['owl_slider_nonce'], 'owl_slider_save_meta')) {
        return;
    }

    // Verificar permisos
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Verificar tipo de post
    if (get_post_type($post_id) !== 'owl_slides') {
        return;
    }

    // Guardar campos
    if (isset($_POST['owl_image_pc'])) {
        update_post_meta($post_id, '_owl_slider_image_pc', esc_url_raw($_POST['owl_image_pc']));
    }

    if (isset($_POST['owl_image_mobile'])) {
        update_post_meta($post_id, '_owl_slider_image_mobile', esc_url_raw($_POST['owl_image_mobile']));
    }

    if (isset($_POST['owl_slide_link'])) {
        update_post_meta($post_id, '_owl_slider_link', esc_url_raw($_POST['owl_slide_link']));
    }

    if (isset($_POST['owl_slide_order'])) {
        update_post_meta($post_id, '_owl_slider_order', absint($_POST['owl_slide_order']));
    }
}
add_action('save_post', 'owl_slider_save_meta_data');

/**
 * Variable global para detectar uso del shortcode
 */
global $owl_slider_used;
$owl_slider_used = false;

/**
 * Shortcode para el slider
 */
function owl_slider_shortcode($atts) {
    global $owl_slider_used;
    $owl_slider_used = true;

    // Atributos configurables
    $atts = shortcode_atts(array(
        'autoplay'      => 'true',
        'autoplay_time' => 3000,      // 3 segundos por slide (antes 5s)
        'speed'         => 300,       // Velocidad de transición en ms
        'loop'          => 'true',
        'aspect_pc'     => '2.63',    // Ratio 1366/520 ≈ 2.63
        'aspect_mobile' => '1.5',     // Ajustar según tus imágenes móviles
    ), $atts, 'owl_slider');

    $args = array(
        'post_type'      => 'owl_slides',
        'posts_per_page' => -1,
        'meta_key'       => '_owl_slider_order',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC'
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '';
    }

    $slider_id = 'owl-slider-' . uniqid();
    $aspect_pc = floatval($atts['aspect_pc']);
    $aspect_mobile = floatval($atts['aspect_mobile']);

    ob_start();
    ?>
    <style>
        /* CSS crítico con alta especificidad */
        .owl-carousel#<?php echo esc_attr($slider_id); ?>,
        #<?php echo esc_attr($slider_id); ?>.owl-carousel {
            width: 100% !important;
            position: relative !important;
            overflow: hidden !important;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        /* Imágenes SIEMPRE visibles y responsive */
        #<?php echo esc_attr($slider_id); ?> .owl-slide-item,
        #<?php echo esc_attr($slider_id); ?> .owl-slide-item picture,
        #<?php echo esc_attr($slider_id); ?> .owl-slide-item a {
            display: block !important;
            width: 100% !important;
            line-height: 0 !important;
        }
        #<?php echo esc_attr($slider_id); ?> .owl-slide-item img {
            width: 100% !important;
            height: auto !important;
            display: block !important;
            max-width: 100% !important;
        }
        /* Ocultar slides extra SOLO antes de inicializar */
        #<?php echo esc_attr($slider_id); ?>:not(.owl-loaded) .owl-slide-item:not(:first-child) {
            display: none !important;
        }
        /* Después de inicializar, Owl controla la visibilidad */
        #<?php echo esc_attr($slider_id); ?>.owl-loaded .owl-slide-item {
            display: block !important;
        }
    </style>

    <div id="<?php echo esc_attr($slider_id); ?>" class="owl-carousel owl-theme" style="width:100%;display:block;opacity:1;visibility:visible;">
        <?php
        $index = 0;
        while ($query->have_posts()) : $query->the_post();
            $image_pc     = get_post_meta(get_the_ID(), '_owl_slider_image_pc', true);
            $image_mobile = get_post_meta(get_the_ID(), '_owl_slider_image_mobile', true);
            $slide_link   = get_post_meta(get_the_ID(), '_owl_slider_link', true);
            $is_first     = ($index === 0);

            // Si no hay imagen mobile, usar la de PC
            if (empty($image_mobile)) {
                $image_mobile = $image_pc;
            }

            // Si no hay ninguna imagen, saltar
            if (empty($image_pc) && empty($image_mobile)) {
                continue;
            }

            // Estilo inline para cada slide
            $slide_style = $is_first ? 'display:block;width:100%;' : '';
        ?>
        <div class="owl-slide-item" style="<?php echo $slide_style; ?>">
            <?php if (!empty($slide_link)) : ?>
            <a href="<?php echo esc_url($slide_link); ?>" style="display:block;line-height:0;">
            <?php endif; ?>
                <picture style="display:block;width:100%;">
                    <?php if (!empty($image_pc)) : ?>
                    <source
                        srcset="<?php echo esc_url($image_pc); ?>"
                        media="(min-width: 768px)">
                    <?php endif; ?>
                    <img
                        src="<?php echo esc_url($image_mobile ?: $image_pc); ?>"
                        alt="<?php the_title_attribute(); ?>"
                        style="width:100%;height:auto;display:block;max-width:100%;"
                        decoding="<?php echo $is_first ? 'sync' : 'async'; ?>"
                        fetchpriority="<?php echo $is_first ? 'high' : 'low'; ?>"
                        loading="<?php echo $is_first ? 'eager' : 'lazy'; ?>">
                </picture>
            <?php if (!empty($slide_link)) : ?>
            </a>
            <?php endif; ?>
        </div>
        <?php
            $index++;
        endwhile;
        wp_reset_postdata();
        ?>
    </div>

    <script>
    (function() {
        function initOwlSlider() {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.owlCarousel !== 'undefined') {
                jQuery('#<?php echo esc_js($slider_id); ?>').owlCarousel({
                    items: 1,
                    loop: <?php echo $atts['loop'] === 'true' ? 'true' : 'false'; ?>,
                    margin: 0,
                    nav: false,
                    dots: false,
                    autoplay: <?php echo $atts['autoplay'] === 'true' ? 'true' : 'false'; ?>,
                    autoplayTimeout: <?php echo absint($atts['autoplay_time']); ?>,
                    autoplayHoverPause: true,
                    smartSpeed: <?php echo absint($atts['speed']); ?>,
                    mouseDrag: true,
                    touchDrag: true
                });
            } else {
                setTimeout(initOwlSlider, 100);
            }
        }
        if (document.readyState === 'complete') {
            initOwlSlider();
        } else {
            window.addEventListener('load', initOwlSlider);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('owl_slider', 'owl_slider_shortcode');

/**
 * Encolar scripts del frontend SOLO cuando se usa el shortcode
 */
function owl_slider_enqueue_frontend_scripts() {
    global $post, $owl_slider_used;

    // Verificar si el shortcode está en el contenido actual
    $load_assets = false;

    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'owl_slider')) {
        $load_assets = true;
    }

    if ($load_assets || $owl_slider_used) {
        // CSS de Owl Carousel
        wp_enqueue_style(
            'owl-carousel',
            OWL_SLIDER_URL . 'assets/css/owl.carousel.min.css',
            array(),
            '2.3.4'
        );

        // JS de Owl Carousel
        wp_enqueue_script(
            'owl-carousel',
            OWL_SLIDER_URL . 'assets/js/owl.carousel.min.js',
            array('jquery'),
            '2.3.4',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'owl_slider_enqueue_frontend_scripts');

/**
 * Fallback: cargar desde CDN si los archivos locales no existen
 */
function owl_slider_fallback_assets() {
    // Si los archivos locales no existen, usar CDN como fallback
    if (!file_exists(OWL_SLIDER_PATH . 'assets/css/owl.carousel.min.css')) {
        wp_deregister_style('owl-carousel');
        wp_register_style(
            'owl-carousel',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css',
            array(),
            '2.3.4'
        );
    }

    if (!file_exists(OWL_SLIDER_PATH . 'assets/js/owl.carousel.min.js')) {
        wp_deregister_script('owl-carousel');
        wp_register_script(
            'owl-carousel',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js',
            array('jquery'),
            '2.3.4',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'owl_slider_fallback_assets', 5);

/**
 * Agregar columnas personalizadas en la lista de slides
 */
function owl_slider_add_admin_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['slide_preview'] = __('Preview', 'owl-slider');
            $new_columns['slide_order'] = __('Orden', 'owl-slider');
        }
    }
    return $new_columns;
}
add_filter('manage_owl_slides_posts_columns', 'owl_slider_add_admin_columns');

/**
 * Contenido de columnas personalizadas
 */
function owl_slider_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'slide_preview':
            $image = get_post_meta($post_id, '_owl_slider_image_pc', true);
            if ($image) {
                echo '<img src="' . esc_url($image) . '" style="max-width: 100px; max-height: 50px; object-fit: cover;">';
            } else {
                echo '—';
            }
            break;
        case 'slide_order':
            $order = get_post_meta($post_id, '_owl_slider_order', true);
            echo esc_html($order !== '' ? $order : '0');
            break;
    }
}
add_action('manage_owl_slides_posts_custom_column', 'owl_slider_admin_column_content', 10, 2);

/**
 * Hacer la columna de orden ordenable
 */
function owl_slider_sortable_columns($columns) {
    $columns['slide_order'] = 'slide_order';
    return $columns;
}
add_filter('manage_edit-owl_slides_sortable_columns', 'owl_slider_sortable_columns');

/**
 * Ordenar por meta value
 */
function owl_slider_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') === 'slide_order') {
        $query->set('meta_key', '_owl_slider_order');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'owl_slider_orderby');

/**
 * Migración de datos del plugin anterior (ejecutar una vez)
 */
function owl_slider_maybe_migrate() {
    if (get_option('owl_slider_migrated')) {
        return;
    }

    // Buscar posts del tipo antiguo 'slides'
    $old_slides = get_posts(array(
        'post_type'      => 'slides',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ));

    foreach ($old_slides as $slide) {
        // Migrar meta keys antiguos a nuevos
        $old_meta = array(
            '_image_pc'    => '_owl_slider_image_pc',
            '_image_mobile'=> '_owl_slider_image_mobile',
            '_slide_link'  => '_owl_slider_link',
            '_slide_order' => '_owl_slider_order',
        );

        foreach ($old_meta as $old_key => $new_key) {
            $value = get_post_meta($slide->ID, $old_key, true);
            if ($value !== '') {
                update_post_meta($slide->ID, $new_key, $value);
            }
        }

        // Cambiar tipo de post
        wp_update_post(array(
            'ID'        => $slide->ID,
            'post_type' => 'owl_slides',
        ));
    }

    update_option('owl_slider_migrated', true);
}
add_action('admin_init', 'owl_slider_maybe_migrate');
