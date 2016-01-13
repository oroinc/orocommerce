<?php

namespace OroB2B\Bundle\FallbackBundle\Entity;

use Doctrine\Common\Collections\Collection;

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
        $values = $values->filter(function (LocalizedFallbackValue $title) use ($locale) {
            return $locale === $title->getLocale();
        });

        $localeTitle = $locale ? $locale->getTitle() : 'default';

        // TODO: implement with fallback
        if ($values->count() === 0 || $values->count() > 1) {
            throw new \LogicException(sprintf('There must be only one %s title', $localeTitle));
        }

        return $values->first();
    }
}
