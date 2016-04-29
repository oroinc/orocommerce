<?php

namespace OroB2B\Bundle\WebsiteBundle\Translation\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;

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
     * @var TranslationStrategyInterface
     */
    protected $mixingStrategy;

    /**
     * @param TranslationsCacheWarmer $innerWarmer
     * @param TranslationStrategyProvider $strategyProvider
     * @param TranslationStrategyInterface $mixingStrategy
     */
    public function __construct(
        TranslationsCacheWarmer $innerWarmer,
        TranslationStrategyProvider $strategyProvider,
        TranslationStrategyInterface $mixingStrategy
    ) {
        $this->innerWarmer = $innerWarmer;
        $this->strategyProvider = $strategyProvider;
        $this->mixingStrategy = $mixingStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $defaultStrategy = $this->strategyProvider->getStrategy();

        // first warm up cache for mixing strategy
        $this->strategyProvider->setStrategy($this->mixingStrategy);
        $this->innerWarmer->warmUp($cacheDir);

        // then warm up cache for default strategy and leave default strategy in provider
        $this->strategyProvider->setStrategy($defaultStrategy);
        $this->innerWarmer->warmUp($cacheDir);
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return $this->innerWarmer->isOptional();
    }
}
