<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Provider;

use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerCriteriaProvider;
use Oro\Bundle\ScopeBundle\Tests\Functional\AbstractScopeProviderTestCase;

class ScopeCustomerCriteriaProviderServiceTest extends AbstractScopeProviderTestCase
{
    public function testProviderRegisteredWithScopeTypes()
    {
        self::assertProviderRegisteredWithScopeTypes(
            ScopeCustomerCriteriaProvider::ACCOUNT,
            [
                'customer_category_visibility',
                'customer_product_visibility',
                'workflow_definition',
                'web_content'
            ]
        );
    }
}
