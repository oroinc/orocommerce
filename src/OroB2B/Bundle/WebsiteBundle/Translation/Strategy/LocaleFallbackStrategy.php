<?php

namespace OroB2B\Bundle\WebsiteBundle\Translation\Strategy;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleFallbackStrategy implements TranslationStrategyInterface
{
    const NAME = 'orob2b_locale_fallback_strategy';
    const CACHE_KEY = 'locale_fallbacks';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @param ManagerRegistry $registry
     * @param CacheProvider $cacheProvider
     */
    public function __construct(ManagerRegistry $registry, CacheProvider $cacheProvider)
    {
        $this->registry = $registry;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleFallbacks()
    {
        $key = static::CACHE_KEY;
        if ($this->cacheProvider->contains($key)) {
            return $this->cacheProvider->fetch($key);
        }
        $localeFallbacks = array_reduce($this->getRootLocales(), function ($result, Locale $locale) {
            return array_merge($result, $this->localeToArray($locale));
        }, []);
        $this->cacheProvider->save($key, $localeFallbacks);
        return $localeFallbacks;
    }

    public function clearCache()
    {
        $this->cacheProvider->delete(static::CACHE_KEY);
    }
    
    /**
     * @return array
     */
    protected function getRootLocales()
    {
        return $this->registry->getManagerForClass('OroB2BWebsiteBundle:Locale')
            ->getRepository('OroB2BWebsiteBundle:Locale')->findRootsWithChildren();
    }

    /**
     * @param Locale $locale
     * @return array
     */
    protected function localeToArray(Locale $locale)
    {
        $children = [];
        foreach ($locale->getChildLocales() as $child) {
            $children = array_merge($children, $this->localeToArray($child));
        }
        return [$locale->getCode() => $children];
    }
}
