<?php

namespace Oro\Bundle\PaymentBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms destination postal codes between object and string representations.
 */
class DestinationPostalCodeTransformer implements DataTransformerInterface
{
    /**
     * @param ArrayCollection|ShippingMethodsConfigsRuleDestinationPostalCode[] $postalCodes
     * @return string
     */
    #[\Override]
    public function transform($postalCodes): mixed
    {
        if (!$postalCodes) {
            return '';
        }

        $postalCodesString = '';
        foreach ($postalCodes as $postalCode) {
            $postalCodesString .= $postalCode->getName() . ', ';
        }
        $postalCodesString = rtrim($postalCodesString, ', ');

        return $postalCodesString;
    }

    /**
     * @param string|null $postalCodesString
     * @return ArrayCollection|ShippingMethodsConfigsRuleDestinationPostalCode[]
     */
    #[\Override]
    public function reverseTransform($postalCodesString): mixed
    {
        $postalCodes = new ArrayCollection();

        if (!$postalCodesString || $postalCodesString === '') {
            return $postalCodes;
        }

        $postalCodeNames = explode(',', $postalCodesString);
        foreach ($postalCodeNames as $postalCodeName) {
            $postalCode = new PaymentMethodsConfigsRuleDestinationPostalCode();

            $postalCode->setName(trim($postalCodeName));
            $postalCodes->add($postalCode);
        }

        return $postalCodes;
    }
}
