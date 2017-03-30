<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;

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
