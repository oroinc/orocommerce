<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Driver;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\SearchBundle\Engine\Orm;

/**
 * @dbIsolationPerTest
 */
class OrmCustomerPartialUpdateDriverTest extends AbstractCustomerPartialUpdateDriverTest
{
    /**
     * {@inheritDoc}
     */
    protected function checkTestToBeSkipped(): void
    {
        $searchEngineName = $this->getContainer()
            ->get('oro_website_search.engine.parameters')
            ->getEngineName();

        if ($searchEngineName !== Orm::ENGINE_NAME) {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }

    protected function getVisibilityCustomerFieldName(Customer $customer): string
    {
        return 'integer.visibility_customer.' . $customer->getId();
    }
}
