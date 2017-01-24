<?php

namespace Oro\Bundle\DPDBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RemoveUsedShippingServiceValidator extends ConstraintValidator
{
    const ALIAS = 'oro_dpd_remove_used_shipping_service_validator';

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
     * @param ManagerRegistry        $doctrine
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(ManagerRegistry $doctrine, ShippingMethodRegistry $registry)
    {
        $this->doctrine = $doctrine;
        $this->registry = $registry;
    }

    /**
     * @param Collection                           $value
     * @param Constraint|RemoveUsedShippingService $constraint
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            $dpdTypeIds = $this->getDpdTypesIds($value);

            $channelId = $this->getChannelId();
            if (null !== $channelId) {
                $methodIdentifier = DPDShippingMethod::IDENTIFIER.'_'.$channelId;
                $shippingMethod = $this->registry->getShippingMethod($methodIdentifier);
                if (null !== $shippingMethod) {
                    $configuredMethods = $this
                        ->doctrine
                        ->getManagerForClass('OroShippingBundle:ShippingMethodConfig')
                        ->getRepository('OroShippingBundle:ShippingMethodConfig')
                        ->findBy(['method' => $methodIdentifier]);
                    if (count($configuredMethods) > 0) {
                        /** @var ShippingMethodConfig $configuredMethod */
                        foreach ($configuredMethods as $configuredMethod) {
                            $configuredTypes = $configuredMethod->getTypeConfigs()->toArray();
                            $enabledTypes = $this->getEnabledTypes($configuredTypes);
                            $diff = array_diff($enabledTypes, $dpdTypeIds);
                            if (0 < count($diff) && (count($enabledTypes) >= count($dpdTypeIds))) {
                                $missingServices = $this
                                    ->doctrine
                                    ->getManagerForClass('OroDPDBundle:ShippingService')
                                    ->getRepository('OroDPDBundle:ShippingService')
                                    ->findBy(['code' => $diff]);

                                $this->addViolations($missingServices, $constraint->message);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Collection $value
     *
     * @return array
     */
    protected function getDpdTypesIds($value)
    {
        $dpdTypesIds = [];
        /** @var ShippingService $dpdService */
        foreach ($value->toArray() as $dpdService) {
            $dpdTypesIds[] = $dpdService->getCode();
        }

        return $dpdTypesIds;
    }

    /**
     * @param array $configuredTypes
     *
     * @return array
     */
    protected function getEnabledTypes($configuredTypes)
    {
        $enabledTypes = [];
        /** @var ShippingMethodTypeConfig $confType */
        foreach ($configuredTypes as $confType) {
            if ($confType->isEnabled()) {
                $enabledTypes[] = $confType->getType();
            }
        }

        return $enabledTypes;
    }

    /**
     * @param array  $missingServices
     * @param string $message
     */
    protected function addViolations($missingServices, $message)
    {
        /** @var ShippingService $service */
        foreach ($missingServices as $service) {
            $this->context->addViolation($message, [
                '{{ service }}' => $service->getDescription(),
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
    protected function getChannelId()
    {
        $id = null;

        $form = $this->getForm();
        while ($form) {
            if ($form->getData() instanceof Channel) {
                $id = $form->getData()->getId();
                break;
            }
            $form = $form->getParent();
        }

        return $id;
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
