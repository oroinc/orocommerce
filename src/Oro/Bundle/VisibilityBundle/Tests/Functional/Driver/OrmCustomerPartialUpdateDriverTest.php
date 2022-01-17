<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm;

/**
 * @dbIsolationPerTest
 */
class OrmCustomerPartialUpdateDriverTest extends AbstractCustomerPartialUpdateDriverTest
{
    /**
     * {@inheritdoc}
     */
    protected function checkTestToBeSkipped()
    {
        $searchEngineName = $this->getContainer()
            ->get('oro_website_search.engine.parameters')
            ->getEngineName();

        if ($searchEngineName !== Orm::ENGINE_NAME) {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }
}
