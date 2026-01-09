<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Builder\Common;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;

/**
 * Defines the contract for builders that create shipping method validator result error collections.
 *
 * Implementations of this interface provide a fluent API for building error collections,
 * supporting both individual error addition and cloning from existing collections.
 */
interface CommonShippingMethodValidatorResultErrorCollectionBuilderInterface
{
    /**
     * @return Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface
     */
    public function getCollection();

    /**
     * @param Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $collection
     *
     * @return $this
     */
    public function cloneAndBuild(
        Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $collection
    );

    /**
     * @param Error\ShippingMethodValidatorResultErrorInterface $error
     *
     * @return $this
     */
    public function addError(Error\ShippingMethodValidatorResultErrorInterface $error);
}
