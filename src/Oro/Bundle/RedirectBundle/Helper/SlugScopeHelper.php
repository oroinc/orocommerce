<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Helper class for Slug Scope parameters' manipulations
 */
class SlugScopeHelper
{
    public static function getScopesHash(Collection $scopes, ?Localization $localization): string
    {
        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope->getId();
        }

        sort($scopeIds);

        return md5(sprintf(
            '%s:%s',
            implode(',', $scopeIds),
            $localization?->getId() ?? ''
        ));
    }
}
