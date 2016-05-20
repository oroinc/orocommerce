<?php

namespace OroB2B\Bundle\FallbackBundle\Entity;

use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

trait FallbackTrait
{
    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Locale|null $locale
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedFallbackValue(Collection $values, Locale $locale = null)
    {
        $filteredValues = $values->filter(
            function (LocalizedFallbackValue $title) use ($locale) {
                return $locale === $title->getLocale();
            }
        );

        if ($filteredValues->count() > 1) {
            $localeTitle = $locale ? $locale->getTitle() : 'default';
            throw new \LogicException(sprintf('There must be only one %s title', $localeTitle));
        }

        $value = $filteredValues->first();
        if ($value) {
            switch ($value->getFallback()) {
                case FallbackType::PARENT_LOCALE:
                    $value = $this->getLocalizedFallbackValue($values, $locale->getParentLocale());
                    break;
                case FallbackType::SYSTEM:
                    $value = $this->getLocalizedFallbackValue($values);
                    break;
                default:
                    return $value;
            }
        }
        if (!$value && $locale !== null) {
            $value = $this->getLocalizedFallbackValue($values); // get default value
        }

        return $value;
    }
}
