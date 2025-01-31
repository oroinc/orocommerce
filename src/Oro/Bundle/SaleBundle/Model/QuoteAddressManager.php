<?php

namespace Oro\Bundle\SaleBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Utils\AddressCopier;
use Oro\Bundle\OrderBundle\Manager\AbstractAddressManager;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;

/**
 * Contains get and update methods for quote addresses.
 */
class QuoteAddressManager extends AbstractAddressManager
{
    private AddressCopier $addressCopier;

    public function __construct(
        ManagerRegistry $doctrine,
        AddressProviderInterface $quoteAddressProvider,
        AddressCopier $addressCopier
    ) {
        parent::__construct($doctrine, $quoteAddressProvider);

        $this->addressCopier = $addressCopier;
    }

    public function updateFromAbstract(
        ?AbstractAddress $address = null,
        ?QuoteAddress $quoteAddress = null
    ): QuoteAddress {
        if (!$quoteAddress) {
            $quoteAddress = new QuoteAddress();
        }

        if (!$address) {
            $address = new QuoteAddress();
        }

        $this->addressCopier->copyToAddress($address, $quoteAddress);

        return $quoteAddress;
    }
}
