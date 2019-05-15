<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * TaxProvider provides a way to interact with taxation system and load/calculate/save tax information
 */
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
     * Load tax and return Result by object
     *
     * @param object $object
     *
     * @return Result
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function loadTax($object);

    /**
     * Get calculated tax result by object
     *
     * @param object $object
     *
     * @return Result
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function getTax($object);

    /**
     * Save tax and return Result by object
     *
     * @param object $object
     * @return Result|null
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function saveTax($object);

    /**
     * Remove tax information assigned to object
     *
     * @param object $object
     *
     * @return boolean
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function removeTax($object);
}
