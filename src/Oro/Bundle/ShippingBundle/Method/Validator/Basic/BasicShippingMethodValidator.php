<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Basic;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;

/**
 * Basic shipping method validator that always returns success.
 *
 * This validator provides a default implementation that accepts all shipping methods without performing any validation,
 * serving as a base validator that can be decorated with additional validation logic.
 */
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

    #[\Override]
    public function validate(ShippingMethodInterface $shippingMethod)
    {
        return $this->commonShippingMethodValidatorResultFactory->createSuccessResult();
    }
}
