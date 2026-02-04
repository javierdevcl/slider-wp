<?php
/**
 * Plugin Name: Owl Carousel Slider
 * Description: Agrega un slider con Owl Carousel usando un Custom Post Type con orden personalizado.
 * Version: 1.1
 * Author: Tu Nombre
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// Registrar Custom Post Type para los Slides
function owl_slider_register_post_type() {
    $args = array(
        'label' => 'Slides',
        'public' => true,
        'show_ui' => true,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-images-alt2',
        'rewrite' => false,
    );
    register_post_type('slides', $args);
}
add_action('init', 'owl_slider_register_post_type');

// Agregar Metabox para Imágenes, Enlace y Orden
function owl_slider_add_meta_boxes() {
    add_meta_box('owl_slider_meta', 'Detalles del Slide', 'owl_slider_meta_callback', 'slides');
}
add_action('add_meta_boxes', 'owl_slider_add_meta_boxes');

function owl_slider_meta_callback($post) {
    $image_pc = get_post_meta($post->ID, '_image_pc', true);
    $image_mobile = get_post_meta($post->ID, '_image_mobile', true);
    $slide_link = get_post_meta($post->ID, '_slide_link', true);
    $slide_order = get_post_meta($post->ID, '_slide_order', true);
    ?>
    <p><label>Imagen para PC:</label></p>
    <input type="text" name="image_pc" value="<?php echo esc_attr($image_pc); ?>" style="width:80%" />
    <button class="upload_image_button button">Seleccionar</button>
    
    <p><label>Imagen para Móvil:</label></p>
    <input type="text" name="image_mobile" value="<?php echo esc_attr($image_mobile); ?>" style="width:80%" />
    <button class="upload_image_button button">Seleccionar</button>
    
    <p><label>Enlace:</label></p>
    <input type="text" name="slide_link" value="<?php echo esc_attr($slide_link); ?>" style="width:100%" />
    
    <p><label>Orden:</label></p>
    <input type="number" name="slide_order" value="<?php echo esc_attr($slide_order); ?>" style="width:100%" />
    
    <script>
    jQuery(document).ready(function($){
        $('.upload_image_button').click(function(e) {
            e.preventDefault();
            var button = $(this);
            var input = button.prev();
            var frame = wp.media({ title: 'Seleccionar Imagen', multiple: false }).open().on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url);
            });
        });
    });
    </script>
    <?php
}

// Guardar Metabox Data
function owl_slider_save_meta_data($post_id) {
    if (isset($_POST['image_pc'])) {
        update_post_meta($post_id, '_image_pc', sanitize_text_field($_POST['image_pc']));
    }
    if (isset($_POST['image_mobile'])) {
        update_post_meta($post_id, '_image_mobile', sanitize_text_field($_POST['image_mobile']));
    }
    if (isset($_POST['slide_link'])) {
        update_post_meta($post_id, '_slide_link', esc_url($_POST['slide_link']));
    }
    if (isset($_POST['slide_order'])) {
        update_post_meta($post_id, '_slide_order', intval($_POST['slide_order']));
    }
}
add_action('save_post', 'owl_slider_save_meta_data');

// Shortcode para el slider
function owl_slider_shortcode() {
    ob_start(); ?><div class="owl-carousel owl-theme owl-loaded">
<?php
$args = array(
    'post_type'      => 'slides',
    'posts_per_page' => -1,
    'meta_key'       => '_slide_order',
    'orderby'        => 'meta_value_num',
    'order'          => 'ASC'
);

$query = new WP_Query($args);

$index = 0; // contador

while ($query->have_posts()) : $query->the_post();

    $image_pc     = get_post_meta(get_the_ID(), '_image_pc', true);
    $image_mobile = get_post_meta(get_the_ID(), '_image_mobile', true);
    $slide_link   = get_post_meta(get_the_ID(), '_slide_link', true);

    // Solo el primer slide
    $is_first = ($index === 0);
?>
    <div class="item slide-owl-wrap">
        <a href="<?php echo esc_url($slide_link); ?>">
            <picture>
                <source 
                    srcset="<?php echo esc_url($image_pc); ?>" 
                    media="(min-width: 768px)"
                    <?php echo $is_first ? 'fetchpriority="high"' : ''; ?>
                >

                <img 
                    src="<?php echo esc_url($image_mobile); ?>"
                    alt="<?php the_title_attribute(); ?>"
                    width="1366"
                    height="520"
                    decoding="async"
                    <?php echo $is_first 
                        ? 'fetchpriority="high" loading="eager"' 
                        : 'loading="lazy"'; ?>
                >
            </picture>
        </a>
    </div>
<?php
    $index++;
endwhile;

wp_reset_postdata();
?>

    </div>
    <script nowprocket>
    jQuery(document).ready(function($){
        $(".owl-carousel").owlCarousel({
            loop: true,
            margin: 0,
            nav: false,
            dots: false,
        	autoplay: true,
       		autoplayTimeout: 5000,
        	autoplayHoverPause: true,
        	animateIn: false,
        	animateOut: false,
        	smartSpeed: 600,
        	mouseDrag: false,
        	touchDrag: true,
            responsive: {
                0: { items: 1 },
                600: { items: 1 },
                1000: { items: 1 }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('owl_slider', 'owl_slider_shortcode');

// Incluir Owl Carousel JS y CSS
function owl_slider_enqueue_scripts() {
    wp_enqueue_style('owl-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css');
    wp_enqueue_script('owl-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js', array('jquery'), '2.3.4', true);
}
add_action('wp_enqueue_scripts', 'owl_slider_enqueue_scripts');
