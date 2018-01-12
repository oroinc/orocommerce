<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;

class BuiltInTaxProvider implements TaxProviderInterface
{
    const NAME = 'built_in';
    const LABEL = 'oro.tax.providers.built_in.label';

    /** @var TaxManager $taxManager */
    private $taxManager;

    /**
     * @param TaxManager $taxManager
     */
    public function __construct(TaxManager $taxManager)
    {
        $this->taxManager = $taxManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
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
     * {@inheritdoc}
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
            $result = $this->taxManager->saveTax($object, false);
            return $result ?: null;
        }

        // No need to store taxes every time
        $storedTaxResult = $this->taxManager->loadTax($object);
        $calculatedTaxResult = $this->taxManager->getTax($object);

        // Compare result objects by serializing results
        // it allows to compare only significant fields
        if ($storedTaxResult->jsonSerialize() !== $calculatedTaxResult->jsonSerialize()) {
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
