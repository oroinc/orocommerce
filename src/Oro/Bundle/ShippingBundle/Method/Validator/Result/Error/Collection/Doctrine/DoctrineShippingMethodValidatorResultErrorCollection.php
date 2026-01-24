<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection;

/**
 * Doctrine-based collection of shipping method validator result errors.
 *
 * This collection extends Doctrine's ArrayCollection to store validation errors, providing
 * a builder factory method for creating new instances with modified error sets.
 */
class DoctrineShippingMethodValidatorResultErrorCollection extends ArrayCollection implements
    Collection\ShippingMethodValidatorResultErrorCollectionInterface
{
    #[\Override]
    public function createCommonBuilder()
    {
        return
            new Collection\Builder\Common\Doctrine\DoctrineCommonShippingMethodValidatorResultErrorCollectionBuilder();
    }
}
