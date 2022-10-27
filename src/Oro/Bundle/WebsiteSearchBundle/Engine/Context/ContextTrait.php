<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;

/**
 * Trait containing useful methods to work with indexation context.
 */
trait ContextTrait
{
    /**
     * Get website identifiers from context with filtering out of empty values
     *
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @return array
     */
    private function getContextWebsiteIds(array $context): array
    {
        return isset($context[AbstractIndexer::CONTEXT_WEBSITE_IDS]) ?
            array_filter($context[AbstractIndexer::CONTEXT_WEBSITE_IDS]) :
            [];
    }

    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @param array $ids
     * @return array
     */
    private function setContextWebsiteIds(array $context, array $ids): array
    {
        $context[AbstractIndexer::CONTEXT_WEBSITE_IDS] = $ids;

        return $context;
    }

    /**
     * Get entity identifiers from context with filtering out of empty values
     *
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @return array
     */
    private function getContextEntityIds(array $context): array
    {
        return isset($context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]) ?
            array_filter($context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]) :
            [];
    }

    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @param array $ids
     * @return array
     */
    private function setContextEntityIds(array $context, array $ids): array
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = $ids;

        return $context;
    }

    /**
     * @param array $context [ 'currentWebsiteId' => int, ...]
     * @return int|null
     */
    private function getContextCurrentWebsiteId(array $context): ?int
    {
        return $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] ?? null;
    }

    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @param int $websiteId
     * @return array
     */
    private function setContextCurrentWebsite(array $context, int $websiteId): array
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = $websiteId;

        return $context;
    }

    private function setContextFieldGroups(array $context, array $fieldGroups = null): array
    {
        if (null !== $fieldGroups) {
            $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = $fieldGroups;
        }

        return $context;
    }

    private function getContextFieldGroups(array $context): ?array
    {
        return $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] ?? null;
    }

    private function hasContextFieldGroup(array $context, string $groupName): bool
    {
        $groups = $this->getContextFieldGroups($context);

        // null means that reindexation for all field groups is required
        if (null === $groups) {
            return true;
        }

        return in_array($groupName, $groups, true);
    }
}
