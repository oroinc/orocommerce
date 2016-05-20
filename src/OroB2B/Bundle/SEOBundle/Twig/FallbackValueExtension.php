<?php

namespace OroB2B\Bundle\SEOBundle\Twig;

use Doctrine\Common\Collections\Collection;
use OroB2B\Bundle\FallbackBundle\Entity\FallbackTrait;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper;

class FallbackValueExtension extends \Twig_Extension
{
    use FallbackTrait;

    /**
     * @var LocaleHelper
     */
    protected $localeHelper;

    /**
     * @param LocaleHelper $localeHelper
     */
    public function __construct(LocaleHelper $localeHelper)
    {
        $this->localeHelper = $localeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('default_fallback_value', [$this, 'getDefaultFallbackValue']),
            new \Twig_SimpleFunction('fallback_locale_value', [$this, 'getFallbackLocaleValue']),
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

        return $this->getFallbackLocaleValue($object->{$getter}());
    }

    /**
     * @param string $locale
     * @param Collection $values
     * @return string|null
     */
    public function getFallbackLocaleValue(Collection $values, $locale = null)
    {
        $locale = $this->localeHelper->getLocale($locale);

        return $this->getLocalizedFallbackValue($values, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_fallback_value_extension';
    }
}
