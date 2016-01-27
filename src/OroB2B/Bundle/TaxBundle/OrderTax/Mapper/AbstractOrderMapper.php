<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;

abstract class AbstractOrderMapper implements TaxMapperInterface
{
    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    /**
     * @param TaxationAddressProvider $addressProvider
     */
    public function __construct(TaxationAddressProvider $addressProvider)
    {
        $this->addressProvider = $addressProvider;
    }

    /**
     * @param Order $order
     * @return AbstractAddress
     */
    public function getOrderAddress(Order $order)
    {
        return $this->addressProvider->getAddressForTaxation($order);
    }
}
