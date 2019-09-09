<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Location: ../");
exit;
