<?php

namespace Oro\Bundle\WebsiteBundle\Translation\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

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
     * @param FrontendHelper $frontendHelper
     * @param TranslationStrategyInterface $frontendStrategy
     */
    public function __construct(
        FrontendHelper $frontendHelper,
        TranslationStrategyInterface $frontendStrategy
    ) {
        $this->frontendHelper = $frontendHelper;
        $this->frontendStrategy = $frontendStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->frontendStrategy->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleFallbacks()
    {
        return $this->frontendStrategy->getLocaleFallbacks();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return $this->frontendHelper->isFrontendRequest();
    }
}
