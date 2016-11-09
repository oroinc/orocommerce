<?php

namespace Oro\Bundle\WebsiteBundle\Translation\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;

class CompositeTranslationCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var TranslationsCacheWarmer
     */
    protected $innerWarmer;

    /**
     * @var TranslationStrategyProvider
     */
    protected $strategyProvider;

    /**
     * @param TranslationsCacheWarmer $innerWarmer
     */
    public function __construct(
        TranslationsCacheWarmer $innerWarmer,
        TranslationStrategyProvider $strategyProvider
    ) {
        $this->innerWarmer = $innerWarmer;
        $this->strategyProvider = $strategyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->strategyProvider->getStrategies() as $strategy) {
            $this->strategyProvider->selectStrategy($strategy->getName());
            $this->innerWarmer->warmUp($cacheDir);
        }
        $this->strategyProvider->resetStrategy();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return $this->innerWarmer->isOptional();
    }
}
