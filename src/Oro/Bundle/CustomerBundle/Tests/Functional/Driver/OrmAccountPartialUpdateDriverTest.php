<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm;

/**
 * @dbIsolationPerTest
 */
class OrmAccountPartialUpdateDriverTest extends AbstractAccountPartialUpdateDriverTest
{
    /**
     * {@inheritdoc}
     */
    protected function isTestSkipped()
    {
        if ($this->getContainer()->getParameter('oro_website_search.engine') !== Orm::ENGINE_NAME) {
            $this->markTestSkipped('Should be tested only with ORM search engine');

            return true;
        }

        return false;
    }
}
