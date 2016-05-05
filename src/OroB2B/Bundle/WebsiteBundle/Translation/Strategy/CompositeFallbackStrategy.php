<?php

namespace OroB2B\Bundle\WebsiteBundle\Translation\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class CompositeFallbackStrategy implements TranslationStrategyInterface
{
    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var TranslationStrategyInterface
     */
    protected $frontendStrategy;

    /**
     * @var TranslationStrategyInterface
     */
    protected $backendStrategy;

    /**
     * @param FrontendHelper $frontendHelper
     * @param TranslationStrategyInterface $frontendStrategy
     * @param TranslationStrategyInterface $backendStrategy
     */
    public function __construct(
        FrontendHelper $frontendHelper,
        TranslationStrategyInterface $frontendStrategy,
        TranslationStrategyInterface $backendStrategy
    ) {
        $this->frontendHelper = $frontendHelper;
        $this->frontendStrategy = $frontendStrategy;
        $this->backendStrategy = $backendStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getCurrentStrategy()->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleFallbacks()
    {
        return $this->getCurrentStrategy()->getLocaleFallbacks();
    }

    /**
     * @return TranslationStrategyInterface
     */
    protected function getCurrentStrategy()
    {
        return $this->frontendHelper->isFrontendRequest() ? $this->frontendStrategy : $this->backendStrategy;
    }
}
