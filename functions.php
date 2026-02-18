<?php
/**
 * توابع و تعاریف قالب سیر و سلوک
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // خروج در صورت دسترسی مستقیم
}

/**
 * 1. تنظیمات اولیه قالب
 */
function seirosolok_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    
    // سایزهای اختصاصی تصاویر
    add_image_size( 'tour-thumb', 600, 400, true );
    add_image_size( 'tour-gallery', 800, 600, true );

    // ثبت منوها
    register_nav_menus( array(
        'primary' => __( 'منوی اصلی', 'seirosolok' ),
        'footer'  => __( 'منوی فوتر', 'seirosolok' ),
    ) );
}
add_action( 'after_setup_theme', 'seirosolok_theme_setup' );

/**
 * 2. فراخوانی فایل‌ها و استایل‌ها
 */
function seirosolok_enqueue_scripts() {
    // 0. لود کردن jQuery (ضروری برای اسکریپت‌های فوتر)
    wp_enqueue_script('jquery');

    // الف) Tailwind CSS (نسخه CDN برای توسعه)
    wp_enqueue_script( 'tailwindcss',  get_template_directory_uri() . '/js/tailwindcss.js', array(), '3.4.1', false );

    // کانفیگ تیلویند و فونت‌ها
    $tailwind_config = "
    tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['YekanBakh', 'sans-serif'],
            },
            colors: {
                primary: {
                    DEFAULT: '#22c55e', 
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    500: '#22c55e',
                    600: '#B31D37', 
                    700: '#15803d',
                    800: '#166534',
                    900: '#111827'
                },
                gold: {
                    DEFAULT: '#f59e0b',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706'
                },
                secondary: {
                    DEFAULT: '#f59e0b',
                    hover: '#d97706'
                },
                dark: '#0f172a',
            }
        }
    }
}";
    wp_add_inline_script( 'tailwindcss', $tailwind_config );

    // ب) آیکون‌ها
    wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );

    // ج) تقویم شمسی
    wp_enqueue_style( 'jalalidatepicker-css', 'https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css', array(), '0.9.6' );
    wp_enqueue_script( 'jalalidatepicker-js', 'https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js', array(), '0.9.6', true );

    // د) استایل اصلی
    wp_enqueue_style( 'seirosolok-style', get_stylesheet_uri() );

    // ه) اسکریپت فیلتر آرشیو
    if ( is_post_type_archive('tour') ) {
        wp_enqueue_script( 'archive-filter', get_template_directory_uri() . '/js/archive-filter.js', array('jquery'), '1.0.0', true );
        wp_localize_script( 'archive-filter', 'seirosolok_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }
}
add_action( 'wp_enqueue_scripts', 'seirosolok_enqueue_scripts' );

/**
 * 3. ثبت پست تایپ‌ها
 */
function seirosolok_register_post_types() {
    // تور
    register_post_type('tour', array(
        'labels' => array(
            'name' => 'تورها',
            'singular_name' => 'تور',
            'add_new' => 'افزودن تور جدید',
            'add_new_item' => 'افزودن تور جدید',
            'edit_item' => 'ویرایش تور',
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-airplane',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'rewrite' => array('slug' => 'tours'),
    ));

    // هتل
    register_post_type('hotel', array(
        'labels' => array(
            'name' => 'هتل‌ها',
            'singular_name' => 'هتل',
        ),
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-building',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
    ));

    // تاکسونومی‌ها
    register_taxonomy('hotel_city', array('tour', 'hotel'), array(
        'labels' => array('name' => 'شهرها (مقاصد)', 'singular_name' => 'شهر'),
        'hierarchical' => true,
        'public' => true,
        'rewrite' => array( 'slug' => 'city', 'with_front' => false ),
    ));

    register_taxonomy('tour_type', 'tour', array(
        'labels' => array('name' => 'انواع تور', 'singular_name' => 'نوع تور'),
        'hierarchical' => true,
        'public' => true,
    ));
}
add_action('init', 'seirosolok_register_post_types');

// --- 4. منطق فیلتر و جستجو (بدون تغییر) ---
function seirosolok_get_tour_filter_args($params) {
    $args = array(
        'post_type'      => 'tour',
        'post_status'    => 'publish',
        'posts_per_page' => 9,
        'meta_query'     => array('relation' => 'AND'),
        'tax_query'      => array('relation' => 'AND')
    );
    if (isset($params['page'])) $args['paged'] = intval($params['page']);
    if (!empty($params['start_point'])) $args['meta_query'][] = array('key' => 'start_point', 'value' => sanitize_text_field($params['start_point']), 'compare' => 'LIKE');
    if (!empty($params['min_price'])) $args['meta_query'][] = array('key' => 'price', 'value' => intval($params['min_price']), 'compare' => '>=', 'type' => 'NUMERIC');
    if (!empty($params['max_price'])) $args['meta_query'][] = array('key' => 'price', 'value' => intval($params['max_price']), 'compare' => '<=', 'type' => 'NUMERIC');
    if (!empty($params['vehicle']) && is_array($params['vehicle'])) $args['meta_query'][] = array('key' => 'vehicle', 'value' => $params['vehicle'], 'compare' => 'IN');
    if (!empty($params['destination'])) $args['tax_query'][] = array('taxonomy' => 'hotel_city', 'field' => 'slug', 'terms' => sanitize_text_field($params['destination']));

    // فیلتر تاریخ
    global $wpdb;
    $date_from = !empty($params['date_from']) ? sanitize_text_field($params['date_from']) : '';
    if ($date_from) {
        $sql = $wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE 'tarikh_%_tarikh_harekat' AND meta_value >= %s", $date_from);
        $date_post_ids = $wpdb->get_col($sql);
        if (!empty($date_post_ids)) $args['post__in'] = $date_post_ids;
        else $args['post__in'] = array(0);
    } 
    return $args;
}

function seirosolok_modify_archive_query($query) {
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('tour')) {
        $params = $_GET; // ساده‌سازی برای خلاصه
        $args = seirosolok_get_tour_filter_args($params);
        foreach ($args as $key => $value) $query->set($key, $value);
    }
}
add_action('pre_get_posts', 'seirosolok_modify_archive_query');

function seirosolok_filter_tours_ajax() {
    $params = $_POST;
    $args = seirosolok_get_tour_filter_args($params);
    $args['paged'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $query = new WP_Query($args);

    ob_start();
    if( $query->have_posts() ) {
        while( $query->have_posts() ) {
            $query->the_post();
            include(locate_template('template-parts/content-tour-card.php')); 
        }
    } else {
        echo '<div class="col-span-full text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-300"><p class="text-gray-500">موردی یافت نشد.</p></div>';
    }
    $html_content = ob_get_clean();
    // صفحه بندی ساده
    $pagination_html = '';
    if ( $query->max_num_pages > 1 ) {
        for ( $i = 1; $i <= $query->max_num_pages; $i++ ) {
            $active_class = ($args['paged'] == $i) ? 'bg-primary-600 text-white' : 'bg-white text-gray-700';
            $pagination_html .= '<button class="ajax-pagination-btn w-10 h-10 rounded-lg flex items-center justify-center font-bold border '.$active_class.'" data-page="' . $i . '">' . $i . '</button>';
        }
    }
    wp_send_json_success(array( 'html' => $html_content, 'count' => $query->found_posts, 'pagination' => $pagination_html ));
    wp_die();
}
add_action('wp_ajax_filter_tours', 'seirosolok_filter_tours_ajax');
add_action('wp_ajax_nopriv_filter_tours', 'seirosolok_filter_tours_ajax');

// --- 5. رزرو تور (جدول + ایجکس) ---
function seirosolok_create_reservations_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'seirosolok_reservations';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tour_name text NOT NULL,
        tour_date varchar(50) NOT NULL,
        fullname varchar(100) NOT NULL,
        mobile varchar(20) NOT NULL,
        status varchar(20) DEFAULT 'pending' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'seirosolok_create_reservations_table');

function seirosolok_submit_reservation_ajax() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'seirosolok_reservations';
    $tour_name = isset($_POST['tour']) ? sanitize_text_field($_POST['tour']) : '';
    $tour_date = isset($_POST['tourDate']) ? sanitize_text_field($_POST['tourDate']) : '';
    $fullname  = isset($_POST['fullname']) ? sanitize_text_field($_POST['fullname']) : '';
    $mobile    = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';

    if (empty($mobile) || empty($fullname)) wp_send_json_error(array('message' => 'نام و موبایل الزامی است.'));

    $inserted = $wpdb->insert($table_name, array('tour_name' => $tour_name, 'tour_date' => $tour_date, 'fullname' => $fullname, 'mobile' => $mobile), array('%s', '%s', '%s', '%s'));
    if ($inserted) wp_send_json_success(array('message' => 'رزرو ثبت شد.'));
    else wp_send_json_error(array('message' => 'خطا در ثبت.'));
}
add_action('wp_ajax_submit_reservation', 'seirosolok_submit_reservation_ajax');
add_action('wp_ajax_nopriv_submit_reservation', 'seirosolok_submit_reservation_ajax');


// ---------------------------------------------------------
// بخش جدید: احراز هویت پیامکی (OTP) - نسخه نهایی
// ---------------------------------------------------------

// الف) ارسال کد OTP
function seirosolok_send_otp_ajax() {
    $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';

    // اعتبارسنجی
    if ( !preg_match('/^09[0-9]{9}$/', $mobile) ) {
        wp_send_json_error(array('message' => 'شماره موبایل معتبر نیست.'));
    }

    // اصلاح فرمت شماره (تبدیل 09 به 989 برای پنل IPPanel)
    $api_mobile = '98' . substr($mobile, 1);

    // تولید کد ۴ رقمی
    $otp = rand(1000, 9999);
    
    // ذخیره در دیتابیس (Transient) برای ۲ دقیقه
    set_transient('otp_' . $mobile, $otp, 120);

    // تنظیمات پنل پیامک
    $url = 'https://api.sms-webservice.com/api/V3/SendTokenSingle';
    $api_key = '247031-1cd694282b64474691f34d19af15ef00';
    $template = 'seirosolokOTP';

    $body = array(
        'ApiKey' => $api_key,
        'TemplateKey' => $template,
        'Mobile' => $api_mobile,
        'Token' => (string)$otp 
    );

    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => json_encode($body),
        'sslverify' => false, // برای جلوگیری از خطای SSL در برخی سرورها
        'timeout'   => 20
    ));

    $is_success = false;
    $api_error_message = 'خطای نامشخص';

    if ( !is_wp_error($response) ) {
        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        // بررسی انواع پاسخ‌های موفقیت پنل
        if ( isset($result['IsSuccess']) && $result['IsSuccess'] ) {
            $is_success = true;
        } elseif ( isset($result['success']) && $result['success'] ) {
            $is_success = true;
        } else {
             // استخراج پیام خطا
             $api_error_message = isset($result['Message']) ? $result['Message'] : (isset($result['message']) ? $result['message'] : 'خطای نامشخص از پنل پیامک');
             if (isset($result['ResultCode'])) {
                 $api_error_message .= ' (کد: ' . $result['ResultCode'] . ')';
             }
        }
    } else {
        $api_error_message = $response->get_error_message();
    }

    if ( $is_success ) {
        wp_send_json_success(array('message' => 'کد تایید به شماره شما پیامک شد.'));
    } else {
        // در نسخه نهایی، خطای دقیق را نمایش می‌دهیم تا ادمین بتواند پیگیری کند
        wp_send_json_error(array('message' => 'خطا در ارسال پیامک: ' . $api_error_message));
    }
}
add_action('wp_ajax_nopriv_send_otp', 'seirosolok_send_otp_ajax');
add_action('wp_ajax_send_otp', 'seirosolok_send_otp_ajax');

// ب) بررسی OTP
function seirosolok_verify_otp_ajax() {
    $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
    $otp    = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

    $saved_otp = get_transient('otp_' . $mobile);

    if ( !$saved_otp || $saved_otp != $otp ) {
        wp_send_json_error(array('message' => 'کد تایید اشتباه یا منقضی شده است.'));
    }

    $user = get_user_by('login', $mobile);

    if ( $user ) {
        // لاگین کاربر قدیمی
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        delete_transient('otp_' . $mobile);
        wp_send_json_success(array('action' => 'login', 'message' => 'خوش آمدید!'));
    } else {
        // کاربر جدید
        set_transient('verified_' . $mobile, true, 300);
        wp_send_json_success(array('action' => 'get_name', 'message' => 'کد تایید شد. لطفاً نام خود را وارد کنید.'));
    }
}
add_action('wp_ajax_nopriv_verify_otp', 'seirosolok_verify_otp_ajax');
add_action('wp_ajax_verify_otp', 'seirosolok_verify_otp_ajax');

// ج) ثبت نام نهایی
function seirosolok_register_user_ajax() {
    $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
    $name   = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $family = isset($_POST['family']) ? sanitize_text_field($_POST['family']) : '';

    if ( !get_transient('verified_' . $mobile) ) {
        wp_send_json_error(array('message' => 'زمان تایید شماره به پایان رسیده. مجددا تلاش کنید.'));
    }

    if ( empty($name) || empty($family) ) {
        wp_send_json_error(array('message' => 'نام و نام خانوادگی الزامی است.'));
    }

    $user_id = wp_create_user($mobile, wp_generate_password(), $mobile . '@seirosolok.com');

    if ( is_wp_error($user_id) ) {
        wp_send_json_error(array('message' => 'خطا در ایجاد حساب: ' . $user_id->get_error_message()));
    }

    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $name,
        'last_name' => $family,
        'display_name' => $name . ' ' . $family
    ));

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    delete_transient('verified_' . $mobile);
    delete_transient('otp_' . $mobile);

    wp_send_json_success(array('action' => 'login', 'message' => 'ثبت نام موفقیت آمیز بود.'));
}
add_action('wp_ajax_nopriv_register_user', 'seirosolok_register_user_ajax');
add_action('wp_ajax_register_user', 'seirosolok_register_user_ajax');