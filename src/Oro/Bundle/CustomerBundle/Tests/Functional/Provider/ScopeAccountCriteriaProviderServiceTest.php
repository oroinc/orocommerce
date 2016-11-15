<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Provider;

use Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider;
use Oro\Bundle\ScopeBundle\Tests\Functional\AbstractScopeProviderTestCase;

class ScopeAccountCriteriaProviderServiceTest extends AbstractScopeProviderTestCase
{
    public function testProviderRegisteredWithScopeTypes()
    {
        self::assertProviderRegisteredWithScopeTypes(
            ScopeAccountCriteriaProvider::ACCOUNT,
            [
                'account_category_visibility',
                'account_product_visibility',
                'workflow_definition'
            ]
        );
    }
}
