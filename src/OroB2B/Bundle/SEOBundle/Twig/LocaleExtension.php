<?php

namespace OroB2B\Bundle\SEOBundle\Twig;

use Doctrine\Common\Collections\Collection;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class LocaleExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_fallback_locale_value';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_fallback_locale_value', array($this, 'getFallbackLocaleValue')),
        );
    }

    /**
     * @param string $locale
     * @param Collection $values
     * @return string|null
     */
    public function getFallbackLocaleValue($locale, Collection $values)
    {
        $filteredValues = $values->filter(function (LocalizedFallbackValue $value) use ($locale) {
            return $locale === $value->getLocale();
        });

        if ($filteredValues ->count() > 1) {
            throw new \LogicException('There must be only one default name');
        } elseif ($filteredValues ->count() === 1) {
            return $filteredValues ->first();
        } elseif ($filteredValues ->count() === 0 && $locale !== null) {
            return $this->getFallbackLocaleValue(null, $values);
        }

        return null;
    }
}
