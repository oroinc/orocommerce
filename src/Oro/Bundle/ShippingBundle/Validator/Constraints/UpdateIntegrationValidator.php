<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdateIntegrationValidator extends ConstraintValidator
{
    /**
     * @var IntegrationShippingMethodFactoryInterface
     */
    private $shippingMethodFactory;

    /**
     * @var ShippingMethodValidatorInterface
     */
    private $shippingMethodValidator;

    /**
     * @var string
     */
    private $violationPath;

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
     * @param Transport  $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Transport) {
            return;
        }

        if (!$value->getChannel() instanceof Channel) {
            return;
        }

        $shippingMethod = $this->shippingMethodFactory->create($value->getChannel());
        $shippingMethodValidatorResult = $this->shippingMethodValidator->validate($shippingMethod);

        $this->handleValidationResult($shippingMethodValidatorResult);
    }

    private function handleValidationResult(ShippingMethodValidatorResultInterface $shippingMethodValidatorResult)
    {
        if ($shippingMethodValidatorResult->getErrors()->isEmpty()) {
            return;
        }

        /** @var ExecutionContextInterface $context */
        $context = $this->context;

        foreach ($shippingMethodValidatorResult->getErrors() as $error) {
            $context
                ->buildViolation($error->getMessage())
                ->setTranslationDomain(null)
                ->atPath($this->violationPath)
                ->addViolation();
        }
    }
}
