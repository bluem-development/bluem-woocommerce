<?php

function bluem_woocommerce_get_mandates_options(): array
{
    return ['shared' => ['default' => 'mandate']];
}

function bluem_woocommerce_get_idin_options(): array
{
    return ['shared' => ['default' => 'identity']];
}

function bluem_woocommerce_get_payments_options(): array
{
    return [
        'shared' => ['default' => 'payment'],
        'wpcf7Active' => ['default' => 'payment-definition'],
    ];
}
