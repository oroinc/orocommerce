<?php

namespace Oro\Bundle\WebsiteSearchBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;

/**
 * Provides functionality to get a website form the search context.
 */
class WebsiteContextManager
{
    use ContextTrait;

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Gets a website ID from the search context if the given website entity exists.
     */
    public function getWebsiteId(array $context): ?int
    {
        return $this->getWebsite($context)?->getId();
    }

    /**
     * Gets a website from the search context if the given website entity exists.
     */
    public function getWebsite(array $context): ?Website
    {
        $websiteId = $this->getContextCurrentWebsiteId($context);
        if (null === $websiteId) {
            return null;
        }

        return $this->doctrine->getManagerForClass(Website::class)->find(Website::class, $websiteId);
    }
}
