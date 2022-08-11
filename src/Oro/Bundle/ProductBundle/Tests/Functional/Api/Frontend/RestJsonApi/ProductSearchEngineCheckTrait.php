<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

trait ProductSearchEngineCheckTrait
{
    /**
     * @return bool
     */
    private function isOrmEngine() : bool
    {
        return \Oro\Bundle\SearchBundle\Engine\Orm::ENGINE_NAME === $this->getSearchEngine();
    }

    /**
     * @return string
     */
    private function getSearchEngine()
    {
        return self::getContainer()
            ->get('oro_website_search.engine.parameters')
            ->getEngineName();
    }
}
