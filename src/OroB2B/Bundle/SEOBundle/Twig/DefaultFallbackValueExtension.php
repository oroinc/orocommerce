<?php

namespace OroB2B\Bundle\SEOBundle\Twig;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class DefaultFallbackValueExtension extends \Twig_Extension
{

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('default_fallback_value', [$this, 'getDefaultFallbackValue']),
        ];
    }

    /**
     * @param object|null $object
     * @return string|null
     */
    public function getDefaultFallbackValue($object, $field)
    {
        if (!$object) {
            return null;
        }

        $getter = 'get' . ucfirst($field);

        $fallbackValues = $object->{$getter}()->filter(function (LocalizedFallbackValue $fallbackValue) {
            return null === $fallbackValue->getLocale();
        });

        if ($fallbackValues->count() > 1) {
            throw new \LogicException('There must be only one default localized fallback value');
        }

        return $fallbackValues->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_default_fallback_value_extension';
    }
}
