<?php
if ( ! defined( 'ABSPATH' ) ) exit;
interface Bluem_Payment_Gateway_Interface
{
    public function process_payment($order_id);
}
