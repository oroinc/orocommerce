<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Builder\Common\Doctrine;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection\Builder;

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
