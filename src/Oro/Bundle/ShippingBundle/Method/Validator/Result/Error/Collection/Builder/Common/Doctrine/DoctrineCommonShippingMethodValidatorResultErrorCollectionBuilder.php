<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Builder\Common\Doctrine;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Builder;

/**
 * Builds Doctrine-based shipping method validator result error collections.
 *
 * This builder provides a fluent API for constructing {@see DoctrineShippingMethodValidatorResultErrorCollection}
 * instances, allowing errors to be added individually or cloned from existing collections.
 */
class DoctrineCommonShippingMethodValidatorResultErrorCollectionBuilder implements
    Builder\Common\CommonShippingMethodValidatorResultErrorCollectionBuilderInterface
{
    /**
     * @var Error\ShippingMethodValidatorResultErrorInterface[]
     */
    private $errors;

    #[\Override]
    public function getCollection()
    {
        return new Error\Collection\Doctrine\DoctrineShippingMethodValidatorResultErrorCollection($this->errors);
    }

    #[\Override]
    public function cloneAndBuild(
        Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $collection
    ) {
        foreach ($collection as $error) {
            $this->errors[] = $error;
        }

        return $this;
    }

    #[\Override]
    public function addError(Error\ShippingMethodValidatorResultErrorInterface $error)
    {
        $this->errors[] = $error;

        return $this;
    }
}
