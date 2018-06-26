<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

/**
 * Validates Elasticsearch parameters, parses them and returns list of affected entities and websites
 */
class IndexerInputValidator
{
    use ContextTrait;

    /** @var WebsiteProviderInterface */
    protected $websiteProvider;

    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /**
     * @param WebsiteProviderInterface $websiteProvider
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function __construct(
        WebsiteProviderInterface $websiteProvider,
        WebsiteSearchMappingProvider $mappingProvider
    ) {
        $this->websiteProvider = $websiteProvider;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param string|array|null $classOrClasses
     * @param array $context
     * $context = [
     *     'entityIds' int[] Array of entities ids to reindex
     *     'websiteIds' int[] Array of websites ids to reindex
     * ]
     *
     * @return array
     */
    public function validateRequestParameters(
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
     * @param string|null $class
     * @return array
     */
    private function getEntitiesToIndex($class = null)
    {
        if ($class) {
            $entityClasses = (array)$class;
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
     * $context = [
     *     'websiteIds' int[] Array of websites ids to reindex
     * ]
     *
     * @return array
     */
    private function getWebsiteIdsToIndex(array $context)
    {
        $websiteIds = $this->getContextWebsiteIds($context);
        if ($websiteIds) {
            return $websiteIds;
        }

        return $this->websiteProvider->getWebsiteIds();
    }
}
