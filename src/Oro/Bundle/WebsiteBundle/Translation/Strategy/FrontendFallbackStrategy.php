<?php

namespace Oro\Bundle\WebsiteBundle\Translation\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

class FrontendFallbackStrategy implements TranslationStrategyInterface
{
    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var TranslationStrategyInterface
     */
    protected $strategy;

    /**
     * @param FrontendHelper $frontendHelper
     * @param TranslationStrategyInterface $strategy
     */
    public function __construct(FrontendHelper $frontendHelper, TranslationStrategyInterface $strategy)
    {
        $this->frontendHelper = $frontendHelper;
        $this->strategy = $strategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->strategy->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleFallbacks()
    {
        return $this->strategy->getLocaleFallbacks();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return $this->frontendHelper->isFrontendRequest();
    }
}
