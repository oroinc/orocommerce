<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * Represents a service that provides a way to interact with taxation system
 * and load/calculate/save tax information.
 */
interface TaxProviderInterface
{
    /**
     * Checks if this provider can be used.
     *
     * @return bool
     */
    public function isApplicable();

    /**
     * Returns the translation key for the provider label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Loads tax and returns tax information for the given object.
     *
     * @param object $object
     *
     * @return Result
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function loadTax($object);

    /**
     * Gets calculated tax information for the given object.
     *
     * @param object $object
     *
     * @return Result
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function getTax($object);

    /**
     * Saves tax and returns tax information for the given object.
     *
     * @param object $object
     *
     * @return Result|null
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function saveTax($object);

    /**
     * Removes tax information assigned to the given object.
     *
     * @param object $object
     *
     * @return bool
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function removeTax($object);
}
