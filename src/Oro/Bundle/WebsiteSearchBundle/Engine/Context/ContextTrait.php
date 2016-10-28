<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;

trait ContextTrait
{
    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @return array
     */
    private function getContextWebsiteIds(array $context)
    {
        return isset($context[AbstractIndexer::CONTEXT_WEBSITE_IDS]) ?
            $context[AbstractIndexer::CONTEXT_WEBSITE_IDS] :
            [];
    }

    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @param array $ids
     * @return array
     */
    private function setContextWebsiteIds(array $context, array $ids)
    {
        $context[AbstractIndexer::CONTEXT_WEBSITE_IDS] = $ids;

        return $context;
    }

    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @return array
     */
    private function getContextEntityIds(array $context)
    {
        return isset($context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]) ?
            $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] :
            [];
    }

    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @param array $ids
     * @return array
     */
    private function setContextEntityIds(array $context, array $ids)
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = $ids;

        return $context;
    }

    /**
     * @param array $context [ 'website_id' => int, ...]
     * @return int|null
     */
    private function getContextCurrentWebsiteId(array $context)
    {
        return isset($context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY]) ?
            $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] :
            null;
    }

    /**
     * @param array $context [ 'website_id' => int, ...]
     * @return int
     * @throws \LogicException
     */
    private function requireContextCurrentWebsiteId(array $context)
    {
        if (!isset($context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY])) {
            throw new \LogicException('Required website id is not passed to context');
        }

        return $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY];
    }

    /**
     * @param array $context [ 'websiteIds' => array, 'entityIds' => array ]
     * @param int $websiteId
     * @return array
     */
    private function setContextCurrentWebsite(array $context, $websiteId)
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = $websiteId;

        return $context;
    }
}
