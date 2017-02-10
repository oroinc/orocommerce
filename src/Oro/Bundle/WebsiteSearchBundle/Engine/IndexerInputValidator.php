<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class IndexerInputValidator
{
    use ContextTrait;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebsiteSearchMappingProvider $mappingProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param string|array $classOrClasses
     * @param array $context
     * @return array
     */
    public function validateReindexRequest(
        $classOrClasses,
        array $context
    ) {

        if (is_array($classOrClasses) && count($classOrClasses) !== 1 && $this->getContextEntityIds($context)) {
            throw new \LogicException('Entity ids passed into context. Please provide single class of entity');
        }

        $entityClassesToIndex = $this->getEntitiesToIndex($classOrClasses);
        $websiteIdsToIndex    = $this->getWebsiteIdsToIndex($context);

        if (empty($entityClassesToIndex)) {
            throw new \LogicException('No entities defined to index');
        }

        return [$entityClassesToIndex, $websiteIdsToIndex];
    }

    /**
     * @param string $class
     * @throws \InvalidArgumentException
     */
    private function ensureEntityClassIsSupported($class)
    {
        if (!$this->mappingProvider->isClassSupported($class)) {
            throw new \InvalidArgumentException('There is no such entity in mapping config.');
        }
    }

    /**
     * @param string $class
     * @return array
     */
    private function getEntitiesToIndex($class = null)
    {
        $entityClasses = (array)$class;
        if ($entityClasses) {
            foreach ($entityClasses as $entityClass) {
                $this->ensureEntityClassIsSupported($entityClass);
            }
        } else {
            $entityClasses = $this->mappingProvider->getEntityClasses();
        }

        return $entityClasses;
    }

    /**
     * @param array $context
     * @return array
     */
    private function getWebsiteIdsToIndex(array $context)
    {
        $websiteIds = $this->getContextWebsiteIds($context);
        if ($websiteIds) {
            return $websiteIds;
        }

        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);

        return $websiteRepository->getWebsiteIdentifiers();
    }
}
