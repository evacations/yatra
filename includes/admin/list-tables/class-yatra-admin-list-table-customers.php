<?php
/**
 * List tables: customers.
 *
 * @package Yatra\admin
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Yatra_Admin_List_Table_Customers', false)) {
    return;
}

if (!class_exists('Yatra_Admin_List_Table', false)) {
    include_once 'abstract-class-yatra-admin-list-table.php';
}

/**
 * Yatra_Admin_List_Table_Customers Class.
 */
class Yatra_Admin_List_Table_Customers extends Yatra_Admin_List_Table
{

    /**
     * Post type.
     *
     * @var string
     */
    protected $list_table_type = 'yatra-customers';


    private $yatra_customer_booking_meta = null;

    /**
     * Render blank state.
     */
    protected function render_blank_state()
    {
        echo '<div class="yatra-BlankState">';

        echo '<h2 class="yatra-BlankState-message">' . esc_html__('When you get new customer, it will appear here.', 'yatra') . '</h2>';

        echo '<div class="yatra-BlankState-buttons">';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Constructor.
     */
    public function __construct()
    {

        parent::__construct();

    }


    /**
     * Define primary column.
     *
     * @return string
     */
    protected function get_primary_column()
    {
        return 'email';
    }

    /**
     * Get row actions to show in the list table.
     *
     * @param array $actions Array of actions.
     * @param WP_Post $post Current post object.
     * @return array
     */
    /*  protected function get_row_actions($actions, $post)
      {
          return array();
      }*/

    /**
     * Define hidden columns.
     *
     * @return array
     */
    protected function define_hidden_columns()
    {
        return array();
    }

    /**
     * Define which columns are sortable.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function define_sortable_columns($columns)
    {
        $custom = array(
            'cb' => 'cb',
            'email' => 'email',
            'date_created' => 'date_created',
        );
        unset($columns['comments']);

        return wp_parse_args($custom, $columns);
    }

    /**
     * Define which columns to show on this screen.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function define_columns($columns)
    {
        $show_columns = array();
        $show_columns['cb'] = $columns['cb'];
        $show_columns['full_name'] = __('Name', 'yatra');
        $show_columns['email'] = __('Primary Email', 'yatra');
        $show_columns['bookings'] = __('Bookings', 'yatra');
        $show_columns['total_booking_price'] = __('Total Booking Price', 'yatra');
        $show_columns['total_spent'] = __('Total Spent', 'yatra');
        $show_columns['date_created'] = __('Date Created', 'yatra');


        return $show_columns;
    }


    /**
     * Pre-fetch any data for the row each column has access to it. the_order global is there for bw compat.
     *
     * @param int $post_id Post ID being shown.
     */
    protected function prepare_row_data($post_id)
    {

        $this->object = get_post($post_id);

        $this->yatra_customer_booking_meta = get_post_meta($post_id, 'yatra_customer_booking_meta', true);

    }


    /**
     * Render columm: render_full_name_column.
     */
    protected function render_full_name_column()
    {
        $fullname = get_post_meta($this->object->ID, 'fullname', true);

        if (empty($fullname)) {

            $email = $this->object->post_title;

            $user = get_user_by('email', $email);

            $fullname = $user->first_name . ' ' . $user->last_name;
        }

        printf('<span>%s</span>', esc_html($fullname));
    }

    /* Render columm: render_bookings_column.
     */
    protected function render_bookings_column()
    {

        printf('<span>%s</span>', esc_html(count($this->yatra_customer_booking_meta)));
    }

    /**
     * Render columm: render_email_column.
     */
    protected function render_email_column()
    {

        $email = $this->object->post_title;

        printf('<span>%s</span>', esc_html($email));
    }

    /**
     * Render columm: order_status.
     */
    protected function render_total_spent_column()
    {

        $amount_array = array();

        foreach ($this->yatra_customer_booking_meta as $booking_meta) {

            $booking_id = isset($booking_meta['booking_id']) ? $booking_meta['booking_id'] : '';

            $currency = isset($booking_meta['currency']) ? $booking_meta['currency'] : '';

            $payment_id = get_post_meta($booking_id, 'yatra_payment_id', true);

            $paid_currency = get_post_meta($payment_id, 'yatra_total_paid_currency', true);

            $paid_currency = empty($paid_currency) ? $currency : $paid_currency;

            $paid_amount = absint(get_post_meta($payment_id, 'yatra_total_paid_amount', true));

            if (!empty($paid_currency)) {
                $amount_array[$paid_currency] = isset($amount_array[$paid_currency]) ? $amount_array[$paid_currency] + $paid_amount : $paid_amount;
            }

        }

        foreach ($amount_array as $currency_key => $amount_spent) {

            $customer_paid_currency = yatra_get_currency_symbols($currency_key);

            printf('<span>%s%s</span>', $customer_paid_currency, absint($amount_spent));
        }
    }

    /**
     * Render columm: render_date_created_column.
     */
    protected function render_date_created_column()
    {


        printf('<span>%s</span>', esc_html(date_i18n(get_option('date_format'), strtotime($this->object->post_date))));
    }


    /**
     * Render columm: render_date_created_column.
     */
    protected function render_total_booking_price_column()
    {
        $currency_symbol = '';

        $total_booking_price = 0;

        foreach ($this->yatra_customer_booking_meta as $meta_key => $booking_params) {

            $currency_symbol = isset($booking_params['currency_symbol']) ? $booking_params['currency_symbol'] : '';

            $total_booking_price += isset($booking_params['total_booking_price']) ? absint($booking_params['total_booking_price']) : 0;
        }

        $total_booking_price_string = $currency_symbol . $total_booking_price;

        printf('<span>%s</span>', esc_html($total_booking_price_string));
    }

}
