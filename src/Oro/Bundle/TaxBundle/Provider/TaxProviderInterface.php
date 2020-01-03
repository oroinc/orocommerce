<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * Represents a service that provides a way to interact with taxation system
 * and load/calculate/save TAX information.
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
     * Loads TAX and returns TAX information for the given object.
     *
     * @param object $object
     *
     * @return Result
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function loadTax($object);

    /**
     * Gets calculated TAX information for the given object.
     *
     * @param object $object
     *
     * @return Result
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function getTax($object);

    /**
     * Saves TAX and returns TAX information for the given object.
     *
     * @param object $object
     *
     * @return Result|null
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function saveTax($object);

    /**
     * Removes TAX information assigned to the given object.
     *
     * @param object $object
     *
     * @return bool
     *
     * @throws TaxationDisabledException if taxation disabled in the system configuration
     */
    public function removeTax($object);
}
