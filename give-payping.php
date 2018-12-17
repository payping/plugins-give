<?php
/*
Plugin Name: give-payping
Description: این پلاگین درگاه پی‌پینگ را به پلاگین give اضافه میکند.
Version: 1.0.0
Author: Erfan Ebrahimi
Author URI: http://www.erfanebrahimi.com
Text Domain: give-Payping
*/

include dirname(__FILE__) . '/php/init.php';
include dirname(__FILE__) . '/php/admin-page.php';
include dirname(__FILE__) . '/php/data.php';
include dirname(__FILE__) . '/php/options.php';
include dirname(__FILE__) . '/php/hooks-actions.php';
include dirname(__FILE__) . '/php/shortcodes.php';

register_activation_hook(__FILE__, 'give_payping_install');
register_activation_hook(__FILE__, 'GivePaypingCreateDataTables');
register_deactivation_hook(__FILE__, 'give_payping_remove');


if (!class_exists('GivePayping')) {
    class GivePayping {
        function GivePayping() {
        }
    }
}

if (class_exists('GivePayping')) {
    $dl_GivePayping = new GivePayping();
}

if (isset($dl_GivePayping)) {
}


function give_payping_install() 
{   
    setGivePaypingOptions();
    
    $options = get_option(OPTION_KEY);
    
    global $wpdb;
    
    $row = $wpdb->get_row(
        "SELECT * "
        . "FROM " . $wpdb->prefix . "posts "
        . "WHERE post_name = 'payping-callback'"
        . "AND post_content = '[payping_redirect]'"
    );
    if (!$row) {
        wp_insert_post(array(
            'post_title'    => 'payping-callback',
            'post_name'     => 'payping-callback',
            'post_content'  => '[payping_redirect]',
            'post_status'   => 'publish',
            'post_type'     => 'page'
        ));
        $post_id = $wpdb->insert_id;
        $options['paypingRedirectPage'] = $post_id;
    }
    
    $row = $wpdb->get_row(
        "SELECT * "
        . "FROM " . $wpdb->prefix . "posts "
        . "WHERE post_name = 'payping-pay-success'"
        . "AND post_content = '<p style=\"text-align: center;\">با تشکر از پرداخت شما - پرداخت شما با موفقیت ثبت شد.</p>'"
    );
    if (!$row) {
        wp_insert_post(array(
            'post_title'    => 'پرداخت موفق',
            'post_name'     => 'payping-pay-success',
            'post_content'  => '<p style=\"text-align: center;\">با تشکر از پرداخت شما - پرداخت شما با موفقیت ثبت شد.</p>',
            'post_status'   => 'publish',
            'post_type'     => 'page'
        ));
        $post_id = $wpdb->insert_id;
        $options['paypingPaySuccessPage'] = $post_id;
    }
    
    $row = $wpdb->get_row(
        "SELECT * "
        . "FROM " . $wpdb->prefix . "posts "
        . "WHERE post_name = 'payping-pay-failed'"
        . "AND post_content = '<p style=\"text-align: center;\">پرداخت شما با مشکل مواجه شد.</p>'"
    );
    if (!$row) {
        wp_insert_post(array(
            'post_title'    => 'پرداخت ناموفق',
            'post_name'     => 'payping-pay-failed',
            'post_content'  => '<p style=\"text-align: center;\">پرداخت شما با مشکل مواجه شد.</p>',
            'post_status'   => 'publish',
            'post_type'     => 'page'
        ));
        $post_id = $wpdb->insert_id;
        $options['paypingPayFailedPage'] = $post_id;
    }
    
    $row = $wpdb->get_row(
        "SELECT * "
        . "FROM " . $wpdb->prefix . "posts "
        . "WHERE post_name = 'payping-pay-cancled'"
        . "AND post_content = '<p style=\"text-align: center;\">پرداخت شما لغو شد.</p>'"
    );
    if (!$row) {
        wp_insert_post(array(
            'post_title'    => 'لغو پرداخت',
            'post_name'     => 'payping-pay-cancled',
            'post_content'  => '<p style=\"text-align: center;\">پرداخت شما لغو شد.</p>',
            'post_status'   => 'publish',
            'post_type'     => 'page'
        ));
        $post_id = $wpdb->insert_id;
        $options['paypingPayCancledPage'] = $post_id;
    }
    
    update_option(OPTION_KEY, $options);
}

function give_payping_remove() 
{
    $options = get_option(OPTION_KEY);
    
    wp_delete_post($options['paypingRedirectPage'], true);
    wp_delete_post($options['paypingPaySuccessPage'], true);
    wp_delete_post($options['paypingPayFailedPage'], true);
    wp_delete_post($options['paypingPayCancledPage'], true);
    
    delete_option(OPTION_KEY);
}

?>
