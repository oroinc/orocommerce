<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection;

class DoctrineShippingMethodValidatorResultErrorCollection extends ArrayCollection implements
    Collection\ShippingMethodValidatorResultErrorCollectionInterface
{
    /**
     * {@inheritDoc}
     */
    public function createCommonBuilder()
    {
        return
            new Collection\Builder\Common\Doctrine\DoctrineCommonShippingMethodValidatorResultErrorCollectionBuilder();
    }
}
