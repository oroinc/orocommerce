<?php

namespace Oro\Bundle\SaleBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\OrderBundle\Manager\AbstractAddressManager;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;
use Oro\Bundle\AccountBundle\Entity\AccountAddress;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;

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

        $quoteAddress->setAccountAddress(null);
        $quoteAddress->setAccountUserAddress(null);

        if ($address instanceof AccountAddress) {
            $quoteAddress->setAccountAddress($address);
        } elseif ($address instanceof AccountUserAddress) {
            $quoteAddress->setAccountUserAddress($address);
        }

        return $quoteAddress;
    }
}
