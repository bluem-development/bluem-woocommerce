<?php

require __DIR__ . '/RequestPersistenceBootstrap.php';

if (!class_exists('WC_Payment_Gateway')) {
    abstract class WC_Payment_Gateway {}
}
require dirname(__DIR__, 2) . '/gateways/Bluem_Bank_Based_Payment_Gateway.php';
