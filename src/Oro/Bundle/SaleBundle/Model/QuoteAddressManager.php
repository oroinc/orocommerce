<?php

namespace Oro\Bundle\SaleBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Manager\AbstractAddressManager;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;

/**
 * Contains get and update methods for quote addresses.
 */
class QuoteAddressManager extends AbstractAddressManager
{
    public function updateFromAbstract(
        AbstractAddress $address = null,
        QuoteAddress $quoteAddress = null
    ): QuoteAddress {
        if (!$quoteAddress) {
            $quoteAddress = new QuoteAddress();
        }

        if (null !== $address) {
            $this->copyAddress($address, $quoteAddress);
        }
        $quoteAddress->setCustomerAddress(null);
        $quoteAddress->setCustomerUserAddress(null);
        if ($address instanceof CustomerAddress) {
            $quoteAddress->setCustomerAddress($address);
        } elseif ($address instanceof CustomerUserAddress) {
            $quoteAddress->setCustomerUserAddress($address);
        }

        return $quoteAddress;
    }
}
