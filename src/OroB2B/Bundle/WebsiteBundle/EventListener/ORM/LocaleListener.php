<?php

namespace OroB2B\Bundle\WebsiteBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Translation\Strategy\LocaleFallbackStrategy;

class LocaleListener
{
    /**
     * @var LocaleFallbackStrategy
     */
    protected $localeFallbackStrategy;

    /**
     * @param LocaleFallbackStrategy $localeFallbackStrategy
     */
    public function __construct(LocaleFallbackStrategy $localeFallbackStrategy)
    {
        $this->localeFallbackStrategy = $localeFallbackStrategy;
    }

    /**
     * @param Locale $locale
     * @param LifecycleEventArgs $event
     * @throws \Exception
     */
    public function postUpdate(Locale $locale, LifecycleEventArgs $event)
    {
        $this->handleChanges();
    }

    /**
     * @param Locale $locale
     * @param LifecycleEventArgs $event
     * @throws \Exception
     */
    public function postPersist(Locale $locale, LifecycleEventArgs $event)
    {
        $this->handleChanges();
    }

    protected function handleChanges()
    {
        $this->localeFallbackStrategy->clearCache();
    }
}
