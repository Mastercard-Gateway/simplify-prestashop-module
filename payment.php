<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/simplifycommerce.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

$simplify = new SimplifyCommerce();
$simplify->processPayment();
