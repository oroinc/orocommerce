<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides choices for form types to select shipping methods.
 */
class ShippingMethodChoicesProvider
{
    private ShippingMethodProviderInterface $shippingMethodProvider;
    private DoctrineHelper $doctrineHelper;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper): void
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getMethods(): array
    {
        $result = [];
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->isEnabled() && $this->hasOptionsConfigurationForm($shippingMethod)) {
                $label = $this->getLabel($shippingMethod);
                //cannot guarantee uniqueness of shipping name
                //need to be sure that we wouldn't override exists one
                if (array_key_exists($label, $result)) {
                    $label .= $this->getShippingMethodIdLabel($shippingMethod);
                }
                $result[$label] = $shippingMethod->getIdentifier();
            }
        }

        return $result;
    }

    public function getMethodTypes(): array
    {
        $result = [];
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->isEnabled() && $this->hasOptionsConfigurationForm($shippingMethod)) {
                $shippingTypes = $shippingMethod->getTypes();
                foreach ($shippingTypes as $shippingType) {
                    $info = json_encode([
                        ShippingDiscount::SHIPPING_METHOD => $shippingMethod->getIdentifier(),
                        ShippingDiscount::SHIPPING_METHOD_TYPE => $shippingType->getIdentifier()
                    ]);
                    $result[$shippingType->getLabel()] = $info;
                }
            }
        }

        return $result;
    }

    private function hasOptionsConfigurationForm(ShippingMethodInterface $shippingMethod): bool
    {
        if (!$shippingMethod->getOptionsConfigurationFormType()) {
            return false;
        }

        $types = $shippingMethod->getTypes();
        if (!$types) {
            return true;
        }

        foreach ($types as $type) {
            if ($type->getOptionsConfigurationFormType()) {
                return true;
            }
        }

        return false;
    }

    private function getLabel(ShippingMethodInterface $method): string
    {
        return $this->loadChannel($method->getIdentifier())?->getName() ?: $method->getLabel();
    }

    private function loadChannel(string $identifier): ?Channel
    {
        //extract entity identifier flat_rate_4 => 4
        $id = substr($identifier, strrpos($identifier, '_') + 1);
        return $this->doctrineHelper->getEntity(Channel::class, (int) $id);
    }

    private function getShippingMethodIdLabel(ShippingMethodInterface $shippingMethod): string
    {
        //extract entity identifier flat_rate_4 => 4
        $id = substr($shippingMethod->getIdentifier(), strrpos($shippingMethod->getIdentifier(), '_') + 1);
        return " ($id)";
    }
}
