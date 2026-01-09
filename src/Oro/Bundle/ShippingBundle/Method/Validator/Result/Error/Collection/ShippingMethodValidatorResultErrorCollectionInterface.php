<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;

/**
 * Defines the contract for collections of shipping method validator result errors.
 *
 * This interface extends Doctrine's Collection to provide type-safe access to validation errors,
 * including a builder factory method for creating modified error collections.
 */
interface ShippingMethodValidatorResultErrorCollectionInterface extends Collection
{
    /**
     * Use this method to recreate same object with different params. For example - in decorators.
     *
     * @return Error\Collection\Builder\Common\CommonShippingMethodValidatorResultErrorCollectionBuilderInterface
     */
    public function createCommonBuilder();

    /**
     * @return Error\ShippingMethodValidatorResultErrorInterface
     */
    public function current();

    /**
     * @return int
     */
    public function key();
}
