<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop\Framework\CartManager\Cart;

class Listing extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $carts;

    public function __construct() {
        $this->getDao()->setCartClass(\OnlineShop\Framework\Factory::getInstance()->getCartManager()->getCartClassName());
    }

    /**
     * @var array
     */
    public function isValidOrderKey($key) {
        if($key == "userId" || $key == "name") {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    function getCarts() {
        if(empty($this->carts)) {
            $this->load();
        }
        return $this->carts;
    }

    /**
     * @param array $carts
     * @return void
     */
    function setCarts($carts) {
        $this->carts = $carts;
    }

}
