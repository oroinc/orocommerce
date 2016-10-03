<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RemoveUsedShippingServiceValidator extends ConstraintValidator
{
    const ALIAS = 'oro_ups_remove_used_shipping_service_validator';
   
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $doctrine
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(ManagerRegistry $doctrine, ShippingMethodRegistry $registry)
    {
        $this->doctrine = $doctrine;
        $this->registry = $registry;
    }

    /**
     * @param Collection $value
     * @param Constraint|RemoveUsedShippingService $constraint
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            $upsTypeIds = $this->getUpsTypesIds($value);

            $methodLabel = $this->getName();
            if ($methodLabel !== null) {
                $shippingMethods = $this->registry->getShippingMethods();
                foreach ($shippingMethods as $shippingMethod) {
                    if ($shippingMethod->getLabel() === $methodLabel) {
                        $identifier = $shippingMethod->getIdentifier();
                        $configuredMethods = $this
                            ->doctrine
                            ->getManagerForClass('OroShippingBundle:ShippingRuleMethodConfig')
                            ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                            ->findBy(['method' => $identifier]);
                        if (count($configuredMethods) > 0) {
                            /** @var ShippingRuleMethodConfig $configuredMethod */
                            foreach ($configuredMethods as $configuredMethod) {
                                $configuredTypes = $configuredMethod->getTypeConfigs()->toArray();
                                $enabledTypes = $this->getEnabledTypes($configuredTypes);
                                $diff = array_diff($enabledTypes, $upsTypeIds);
                                if (0 < count($diff) && (count($enabledTypes) >= count($upsTypeIds))) {
                                    $missingServices = $this
                                        ->doctrine
                                        ->getManagerForClass('OroUPSBundle:ShippingService')
                                        ->getRepository('OroUPSBundle:ShippingService')
                                        ->findBy(['code' => $diff, 'country' => $this->getCountry()]);
                                    
                                    $this->addViolations($missingServices, $constraint->message);
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param Collection $value
     * @return array
     */
    protected function getUpsTypesIds($value)
    {
        $upsTypesIds = [];
        /** @var ShippingService $upsService */
        foreach ($value->toArray() as $upsService) {
            $upsTypesIds[] = $upsService->getCode();
        }
        return $upsTypesIds;
    }

    /**
     * @param array $configuredTypes
     * @return array
     */
    protected function getEnabledTypes($configuredTypes)
    {
        $enabledTypes = [];
        /** @var ShippingRuleMethodTypeConfig $confType */
        foreach ($configuredTypes as $confType) {
            if ($confType->isEnabled()) {
                $enabledTypes[] = $confType->getType();
            }
        }
        return $enabledTypes;
    }
    
    /**
     * @param array $missingServices
     * @param string $message
     */
    protected function addViolations($missingServices, $message)
    {
        /** @var ShippingService $service */
        foreach ($missingServices as $service) {
            $this->context->addViolation($message, [
                '{{ service }}' => $service->getDescription()
            ]);
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }
        return $this->propertyAccessor;
    }

    /**
     * @return string|null
     */
    protected function getName()
    {
        $name = null;

        $form = $this->getForm();
        while ($form) {
            if ($form->getData() instanceof Channel) {
                $name = $form->getData()->getName();
                break;
            }
            $form = $form->getParent();
        }
        return $name;
    }

    /**
     * @return Country|null
     */
    protected function getCountry()
    {
        $country = null;

        $form = $this->getForm();
        while ($form) {
            if ($form->getData() instanceof UPSTransport) {
                $country = $form->getData()->getCountry();
                break;
            }
            $form = $form->getParent();
        }
        return $country;
    }

    /**
     * @return FormInterface
     */
    protected function getForm()
    {
        return $this->getPropertyAccessor()->getValue($this->context->getRoot(), $this->getFormPath());
    }

    /**
     * @return string
     */
    protected function getFormPath()
    {
        $path = $this->context->getPropertyPath();
        $path = str_replace(['children', '.'], '', $path);
        $path = preg_replace('/\][^\]]*$/', ']', $path);
        return $path;
    }
}
