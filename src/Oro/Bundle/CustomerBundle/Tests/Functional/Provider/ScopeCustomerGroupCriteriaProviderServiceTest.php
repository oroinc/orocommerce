<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Provider;

use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\ScopeBundle\Tests\Functional\AbstractScopeProviderTestCase;

class ScopeCustomerGroupCriteriaProviderServiceTest extends AbstractScopeProviderTestCase
{
    public function testProviderRegisteredWithScopeTypes()
    {
        self::assertProviderRegisteredWithScopeTypes(
            ScopeCustomerGroupCriteriaProvider::FIELD_NAME,
            [
                'customer_group_category_visibility',
                'customer_group_product_visibility',
                'workflow_definition',
                'web_content'
            ]
        );
    }
}
