<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates if shipping integration can be deleted.
 * Checks if integration does not have shipping methods using it.
 */
class UpdateIntegrationValidator extends ConstraintValidator
{
    private IntegrationShippingMethodFactoryInterface $shippingMethodFactory;
    private ShippingMethodValidatorInterface $shippingMethodValidator;
    private string $violationPath;

    public function __construct(
        IntegrationShippingMethodFactoryInterface $shippingMethodFactory,
        ShippingMethodValidatorInterface $shippingMethodValidator,
        string $violationPath
    ) {
        $this->shippingMethodFactory = $shippingMethodFactory;
        $this->shippingMethodValidator = $shippingMethodValidator;
        $this->violationPath = $violationPath;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof Transport) {
            return;
        }

        if (!$value->getChannel() instanceof Channel) {
            return;
        }

        $errors = $this->shippingMethodValidator
            ->validate($this->shippingMethodFactory->create($value->getChannel()))
            ->getErrors();
        if ($errors->isEmpty()) {
            return;
        }

        foreach ($errors as $error) {
            $this->context->buildViolation($error->getMessage())
                ->atPath($this->violationPath)
                ->addViolation();
        }
    }
}
