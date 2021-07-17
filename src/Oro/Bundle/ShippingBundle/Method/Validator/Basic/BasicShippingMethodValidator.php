<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Basic;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;

class BasicShippingMethodValidator implements ShippingMethodValidatorInterface
{
    /**
     * @var Common\CommonShippingMethodValidatorResultFactoryInterface
     */
    private $commonShippingMethodValidatorResultFactory;

    public function __construct(
        Common\CommonShippingMethodValidatorResultFactoryInterface $commonShippingMethodValidatorResultFactory
    ) {
        $this->commonShippingMethodValidatorResultFactory = $commonShippingMethodValidatorResultFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ShippingMethodInterface $shippingMethod)
    {
        return $this->commonShippingMethodValidatorResultFactory->createSuccessResult();
    }
}
