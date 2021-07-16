<?php
namespace Oro\Bundle\WebsiteSearchBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;

class WebsiteContextManager
{
    use ContextTrait;

    /** @var  DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Returns website id from context if according website exists
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @return int|null
     */
    public function getWebsiteId(array $context)
    {
        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
        $websiteId = $this->getContextCurrentWebsiteId($context);

        if ($websiteRepository->checkWebsiteExists($websiteId)) {
            return $websiteId;
        }

        return null;
    }

    /**
     * Returns website id from context if according website exists
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @return Website|null
     */
    public function getWebsite(array $context)
    {
        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
        $websiteId = $this->getContextCurrentWebsiteId($context);

        if ($websiteId === null) {
            return null;
        }

        return $websiteRepository->find($websiteId);
    }
}
