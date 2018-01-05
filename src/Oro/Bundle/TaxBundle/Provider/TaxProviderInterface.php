<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;

interface TaxProviderInterface
{
    /**
     * Check if provider can be used
     *
     * @return bool
     */
    public function isApplicable();

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName();

    /**
     * Return label key
     *
     * @return string
     */
    public function getLabel();

    /**
     * Creates new or returns existing TaxValue instance based on object
     *
     * @param object $object
     *
     * @return TaxValue
     */
    public function createTaxValue($object);

    /**
     * Load tax and return Result by object
     *
     * @param object $object
     *
     * @return Result
     */
    public function loadTax($object);

    /**
     * Get calculated tax result by object
     *
     * @param object $object
     *
     * @return Result
     */
    public function getTax($object);

    /**
     * Save tax and return Result by object
     *
     * @param object $object
     *
     * @return Result|null
     */
    public function saveTax($object);

    /**
     * Remove tax value assigned to object
     *
     * @param object $object
     *
     * @return boolean
     */
    public function removeTax($object);
}
