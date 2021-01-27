<?php

namespace Oro\Bundle\SaleBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Manager\AbstractAddressManager;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;

class QuoteAddressManager extends AbstractAddressManager
{
    /** @var string */
    protected $quoteAddressClass;

    /**
     * @param QuoteAddressProvider $quoteAddressProvider
     * @param ManagerRegistry $registry
     * @param string $quoteAddressClass
     */
    public function __construct(
        QuoteAddressProvider $quoteAddressProvider,
        ManagerRegistry $registry,
        $quoteAddressClass
    ) {
        $this->quoteAddressClass = $quoteAddressClass;

        $this->map = new ArrayCollection();

        parent::__construct($quoteAddressProvider, $registry);
    }

    /**
     * @param AbstractAddress $address
     * @param QuoteAddress $quoteAddress
     *
     * @return QuoteAddress
     */
    public function updateFromAbstract(AbstractAddress $address = null, QuoteAddress $quoteAddress = null)
    {
        if (!$quoteAddress) {
            $quoteAddress = new $this->quoteAddressClass();
        }

        if ($address) {
            $addressClassName = ClassUtils::getClass($address);
            $addressMetadata = $this->registry->getManagerForClass($addressClassName)
                ->getClassMetadata($addressClassName);

            foreach ($addressMetadata->getFieldNames() as $fieldName) {
                $this->setValue($address, $quoteAddress, $fieldName);
            }

            foreach ($addressMetadata->getAssociationNames() as $associationName) {
                $this->setValue($address, $quoteAddress, $associationName);
            }
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
