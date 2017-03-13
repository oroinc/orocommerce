<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;
use Oro\Bundle\ShippingBundle\Method\Factory\MethodTypeChangeEventFactoryInterface;
use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RemoveUsedShippingServiceValidator extends ConstraintValidator
{
    const ALIAS = 'oro_ups_remove_used_shipping_service_validator';

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ShippingServiceRepository
     */
    private $serviceRepository;

    /**
     * @var MethodTypeChangeEventFactoryInterface
     */
    private $typeChangeEventFactory;

    /**
     * @param IntegrationIdentifierGeneratorInterface $identifierGenerator
     * @param EventDispatcherInterface                $dispatcher
     * @param ShippingServiceRepository               $serviceRepository
     * @param MethodTypeChangeEventFactoryInterface   $typeChangeEventFactory
     */
    public function __construct(
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        EventDispatcherInterface $dispatcher,
        ShippingServiceRepository $serviceRepository,
        MethodTypeChangeEventFactoryInterface $typeChangeEventFactory
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->dispatcher = $dispatcher;
        $this->serviceRepository = $serviceRepository;
        $this->typeChangeEventFactory = $typeChangeEventFactory;
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

        $event = $this->buildMethodTypeChangeEvent($value);
        $this->dispatcher->dispatch(MethodTypeChangeEvent::NAME, $event);

        if ($event->hasErrors()) {
            $this->handleErrors($event, $value);
        }
    }

    /**
     * @param MethodTypeChangeEvent $event
     * @param UPSTransport          $transport
     */
    private function handleErrors(MethodTypeChangeEvent $event, UPSTransport $transport)
    {
        $serviceDescriptions = $this->getAllServiceDescriptions($transport->getCountry());

        $errorDescriptions = [];
        foreach ($event->getErrorTypes() as $type) {
            if (!array_key_exists($type, $serviceDescriptions)) {
                continue;
            }

            $errorDescriptions[] = $serviceDescriptions[$type];
        }

        $this->addViolation(
            $event->getErrorMessagePlaceholder(),
            implode(', ', $errorDescriptions)
        );
    }

    /**
     * @param string $messagePlaceholder
     * @param string $errorTypes
     */
    private function addViolation($messagePlaceholder, $errorTypes)
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;

        $context->buildViolation($messagePlaceholder)
            ->setParameter('%types%', $errorTypes)
            ->setTranslationDomain(null)
            ->atPath('applicableShippingServices')
            ->addViolation();
    }

    /**
     * @param UPSTransport $transport
     *
     * @return MethodTypeChangeEvent
     */
    private function buildMethodTypeChangeEvent(UPSTransport $transport)
    {
        $selectedServiceCodes = $this->getSelectedServiceCodes($transport);

        $methodIdentifier = $this->identifierGenerator->generateIdentifier(
            $transport->getChannel()
        );

        return $this->typeChangeEventFactory->create($selectedServiceCodes, $methodIdentifier);
    }

    /**
     * @param Country $country
     *
     * @return array<code => description>
     */
    private function getAllServiceDescriptions(Country $country)
    {
        $shippingServices = $this->serviceRepository->getShippingServicesByCountry($country);

        $names = [];
        foreach ($shippingServices as $service) {
            $names[$service->getCode()] = $service->getDescription();
        }

        return $names;
    }

    /**
     * @param UPSTransport $transport
     *
     * @return string[]
     */
    private function getSelectedServiceCodes(UPSTransport $transport)
    {
        $selectedServices = $transport->getApplicableShippingServices();

        $codes = [];
        foreach ($selectedServices as $service) {
            $codes[] = $service->getCode();
        }

        return $codes;
    }
}
