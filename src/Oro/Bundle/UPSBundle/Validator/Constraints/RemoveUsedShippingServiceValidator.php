<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\ChannelBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\MethodTypeChangeEventFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RemoveUsedShippingServiceValidator extends ConstraintValidator
{
    const ALIAS = 'oro_ups_remove_used_shipping_service_validator';

    /**
     * @var IntegrationShippingMethodFactoryInterface
     */
    private $integrationShippingMethodFactory;

    /**
     * @var ShippingMethodValidatorInterface
     */
    private $shippingMethodValidator;

    /**
     * @param IntegrationShippingMethodFactoryInterface $integrationShippingMethodFactory
     * @param ShippingMethodValidatorInterface          $shippingMethodValidator
     */
    public function __construct(
        IntegrationShippingMethodFactoryInterface $integrationShippingMethodFactory,
        ShippingMethodValidatorInterface $shippingMethodValidator
    ) {
        $this->integrationShippingMethodFactory = $integrationShippingMethodFactory;
        $this->shippingMethodValidator = $shippingMethodValidator;
    }

    /**
     * @param UPSTransport                         $value
     * @param Constraint|RemoveUsedShippingService $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof UPSTransport) {
            return;
        }

        if (!$value->getChannel()) {
            return;
        }

        $upsShippingMethod = $this->integrationShippingMethodFactory->create($value->getChannel());
        $shippingMethodValidatorResult = $this->shippingMethodValidator->validate($upsShippingMethod);

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
            $context->buildViolation($error->getMessage())
                ->setTranslationDomain(null)
                ->atPath('applicableShippingServices')
                ->addViolation();
        }
    }
}
