<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxManager;

/**
 * Tax provider allows to use built-in tax logic.
 */
class BuiltInTaxProvider implements TaxProviderInterface
{
    const LABEL = 'oro.tax.providers.built_in.label';

    /** @var TaxManager $taxManager */
    private $taxManager;

    public function __construct(TaxManager $taxManager)
    {
        $this->taxManager = $taxManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return self::LABEL;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * Creates new or returns existing TaxValue instance based on object
     *
     * This method is specific for BuiltInProvider because it stores tax value data in DB
     *
     * @param object $object
     *
     * @return TaxValue
     */
    public function createTaxValue($object)
    {
        return $this->taxManager->createTaxValue($object);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTax($object)
    {
        return $this->taxManager->loadTax($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getTax($object)
    {
        return $this->taxManager->getTax($object);
    }

    /**
     * {@inheritdoc}
     */
    public function saveTax($object)
    {
        // Always calculate taxes for entity which doesn't have it
        $taxValue = $this->taxManager->getTaxValue($object);
        if (!$taxValue->getId()) {
            $result = $this->taxManager->saveTax($object);
            return $result ?: null;
        }

        // No need to store taxes every time
        $storedTaxResult = $this->taxManager->loadTax($object);
        $calculatedTaxResult = $this->taxManager->getTax($object);

        // Compare result objects by serializing results
        // it allows to compare only significant fields
        if (json_encode($storedTaxResult) !== json_encode($calculatedTaxResult)) {
            $result = $this->taxManager->saveTax($object, false);
            return $result ?: null;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTax($object)
    {
        return $this->taxManager->removeTax($object);
    }
}
