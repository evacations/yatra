<?php
/**
 * Handle frontend forms.
 *
 * @package Yatra/Classes/
 */

defined('ABSPATH') || exit;

/**
 * Yatra_Form_Handler class.
 */
class Yatra_Form_Handler
{

    /**
     * Hook in methods.
     */
    public static function init()
    {
        add_action('template_redirect', array(__CLASS__, 'yatra_save_account_details'));
        add_action('template_redirect', array(__CLASS__, 'yatra_change_user_password'));
        add_action('template_redirect', array(__CLASS__, 'process_login'));
        add_action('template_redirect', array(__CLASS__, 'register_user'));
        add_action('template_redirect', array(__CLASS__, 'book_selected_tour'));
        //add_action('wp_loaded', array(__CLASS__, 'checkout_action'), 20);


    }

    public static function book_selected_tour()
    {
        $nonce_value = yatra_get_var($_REQUEST['yatra-book-selected-tour-nonce'], yatra_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

        if (!wp_verify_nonce($nonce_value, 'yatra_book_selected_tour_nonce')) {
            return;
        }


        if (empty($_POST['action']) || 'yatra_book_selected_tour_nonce' !== $_POST['action']) {
            return;
        }

        if (yatra_enable_guest_checkout()) {

            $valid_data = Yatra_Checkout_Form::get_instance()->valid_tour_checkout_form($_POST);


            if (yatra_instance()->yatra_error->has_errors()) {

                return;
            }
        } else {

            $current_user_id = get_current_user_id();

            $user_data = get_userdata($current_user_id);

            $valid_data['yatra_tour_customer_info'] = array(

                'email' => $user_data->user_email
            );
        }

        $payment_gateway_id = isset($_POST['yatra-payment-gateway']) ? sanitize_text_field($_POST['yatra-payment-gateway']) : 'yatra-not-gateway';

        $yatra_get_active_payment_gateways = yatra_get_active_payment_gateways();

        if (!in_array($payment_gateway_id, $yatra_get_active_payment_gateways) && count($yatra_get_active_payment_gateways) > 0) {

            yatra_instance()->yatra_error->add('yatra_form_validation_errors', __('Please select at least one payment gateway', 'yatra'));

            return;

        }

        $yatra_booking = new Yatra_Tour_Booking();

        $booking_id = (int)$yatra_booking->book($valid_data);

        if ($booking_id > 0) {

            yatra_clear_session('yatra_tour_cart');

            if (in_array($payment_gateway_id, $yatra_get_active_payment_gateways)) {

                do_action('yatra_payment_checkout_payment_gateway_' . $payment_gateway_id, $booking_id);

            }

            $success_redirect_page_id = get_option('yatra_thankyou_page');

            $page_permalink = get_permalink($success_redirect_page_id);

            wp_safe_redirect($page_permalink);

            exit;
        }
        yatra_instance()->yatra_error->add('yatra_checkout_error', __('Could not booked, please try again', 'yatra'));

    }

    /**
     * Save the password/account details and redirect back to the my account page.
     */
    public static function yatra_save_account_details()
    {

        $nonce_value = yatra_get_var($_REQUEST['yatra-save-account-details-nonce'], yatra_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

        if (!wp_verify_nonce($nonce_value, 'yatra_save_account_details')) {
            return;
        }


        if (empty($_POST['action']) || 'yatra_save_account_details' !== $_POST['action']) {
            return;
        }

        $user_id = get_current_user_id();

        if ($user_id < 1) {
            return;
        }

        $yatra_user = new Yatra_User_Form();

        $valid_form_data = $yatra_user->valid_profile_form_data($_POST);

        $user_custom_meta_keys = $yatra_user->user_custom_meta_keys();
        // New user data.
        $user = new stdClass();
        $user->ID = $user_id;

        if (yatra_instance()->yatra_error->has_errors()) {

            return;
        }
        foreach ($valid_form_data as $valid_key => $valid_value) {

            if (!in_array($valid_key, $user_custom_meta_keys)) {
                $user->$valid_key = $valid_value;
            } else {
                update_user_meta($user_id, $valid_key, $valid_value);
            }
        }


        wp_update_user($user);

        yatra_instance()->yatra_messages->add('yatra_my_account_messages', __('User profile successfully updated.', 'yatra'), 'success');

    }


    public static function yatra_change_user_password()
    {

        $nonce_value = yatra_get_var($_REQUEST['yatra-change-user-password-nonce'], yatra_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

        if (!wp_verify_nonce($nonce_value, 'yatra_change_user_password')) {
            return;
        }
        if (empty($_POST['action']) || 'yatra_change_user_password' !== $_POST['action']) {
            return;
        }
        $user_id = get_current_user_id();

        if ($user_id < 1) {
            return;
        }

        $yatra_user = new Yatra_User_Form();

        $valid_form_data = $yatra_user->valid_change_password_form_data($_POST);

        if (yatra_instance()->yatra_error->has_errors()) {

            return;
        }

        $current_user = get_user_by('id', $user_id);

        $old_password = isset($valid_form_data['yatra_old_password']) ? $valid_form_data['yatra_old_password'] : '';

        $yatra_new_password = isset($valid_form_data['yatra_new_password']) ? $valid_form_data['yatra_new_password'] : '';

        if (!wp_check_password($old_password, $current_user->user_pass, $current_user->ID) || empty($yatra_new_password)) {

            yatra_instance()->yatra_error->add('yatra_form_validation_errors', __('Old Password doesn\'t match', 'yatra'));

            return;
        }

        if (yatra_instance()->yatra_error->has_errors()) {

            return;
        }

        $user = new stdClass();

        $user->ID = $user_id;

        $user->user_pass = $yatra_new_password;

        wp_update_user($user);

        yatra_instance()->yatra_messages->add('yatra_my_account_messages', __('Password successfully changed.', 'yatra'), 'success');


    }

    public static function process_login()
    {
        $nonce_value = yatra_get_var($_REQUEST['yatra-login-nonce'], yatra_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

        if (isset($_POST['login'], $_POST['username'], $_POST['password']) && wp_verify_nonce($nonce_value, 'yatra-login')) {

            try {
                $creds = array(
                    'user_login' => trim(wp_unslash($_POST['username'])), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    'user_password' => $_POST['password'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    'remember' => isset($_POST['rememberme']), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                );

                $validation_error = new WP_Error();

                $validation_error = apply_filters('yatra_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password']);

                if ($validation_error->get_error_code()) {
                    throw new Exception($validation_error->get_error_message());
                }

                if (empty($creds['user_login'])) {
                    throw new Exception(__('Username is required.', 'yatra'));
                }

                // On multisite, ensure user exists on current site, if not add them before allowing login.
                if (is_multisite()) {
                    $user_data = get_user_by(is_email($creds['user_login']) ? 'email' : 'login', $creds['user_login']);

                    if ($user_data && !is_user_member_of_blog($user_data->ID, get_current_blog_id())) {
                        add_user_to_blog(get_current_blog_id(), $user_data->ID, 'customer');
                    }
                }


                // Perform the login.
                $user = wp_signon(apply_filters('yatra_login_credentials', $creds), is_ssl());

                if (is_wp_error($user)) {
                    $message = $user->get_error_message();
                    $message = str_replace(esc_html($creds['user_login']), esc_html($creds['user_login']), $message);
                    throw new Exception($message);
                } else {

                    if (!empty($_POST['redirect'])) {
                        $redirect = wp_unslash($_POST['redirect']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    } else {
                        $redirect = yatra_get_my_account_page(true);
                    }
                    wp_redirect($redirect); // phpcs:ignore
                    exit;
                }
            } catch (Exception $e) {
                yatra_instance()->yatra_error->add('yatra_login_error_message', $e->getMessage());
                do_action('yatra_login_failed');
            }
        }
    }

    public static function register_user()
    {
        $nonce_value = yatra_get_var($_REQUEST['yatra-registration-nonce'], yatra_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

        if (isset($_POST['registration'], $_POST['yatra_username'], $_POST['yatra_email'], $_POST['yatra_password'], $_POST['yatra_confirm_password']) && wp_verify_nonce($nonce_value, 'yatra-registration')) {

            $valid_data = Yatra_Checkout_Form::get_instance()->create_account_valid_form_data($_POST);

            if (yatra_instance()->yatra_error->has_errors()) {

                return;
            }
            $email = isset($valid_data['yatra_email']) ? $valid_data['yatra_email'] : '';


            $password = isset($valid_data['yatra_password']) ? $valid_data['yatra_password'] : '';

            $confirm_password = isset($valid_data['yatra_confirm_password']) ? $valid_data['yatra_confirm_password'] : '';

            $username = isset($valid_data['yatra_username']) ? $valid_data['yatra_username'] : '';

            $is_email_already_exists = email_exists($email);

            $username_exists = username_exists($username);

            if ($is_email_already_exists || $username_exists) {

                yatra_instance()->yatra_error->add('yatra_form_validation_errors', __('Email or Usrename already exists, please try again..', 'yatra'));

                return;
            }

            if (!$is_email_already_exists && $password === $confirm_password && !empty($password) && !empty($email) && !$username_exists && !empty($username)) {

                $userdata = array(
                    'user_pass' => $password,
                    'user_email' => $email,
                    'user_login' => $username
                );
                $user_id = wp_insert_user($userdata);

                update_user_meta($user_id, 'yatra_user', true);

                if ($user_id) {
                    $creds = array(
                        'user_login' => $username,
                        'user_password' => $password,
                    );

                    $user = wp_signon(apply_filters('yatra_login_credentials', $creds), is_ssl());

                    if (!is_wp_error($user)) {

                        wp_safe_redirect(get_permalink());
                    }
                }


            } else {

                yatra_instance()->yatra_error->add('yatra_form_validation_errors', __('Something wrong on registration, please check all form fields once.', 'yatra'));

                return;
            }

        }

    }
}

Yatra_Form_Handler::init();
