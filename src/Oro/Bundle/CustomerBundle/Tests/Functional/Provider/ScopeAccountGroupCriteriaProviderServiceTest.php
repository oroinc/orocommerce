<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Provider;

use Oro\Bundle\CustomerBundle\Provider\ScopeAccountGroupCriteriaProvider;
use Oro\Bundle\ScopeBundle\Tests\Functional\AbstractScopeProviderTestCase;

class ScopeAccountGroupCriteriaProviderServiceTest extends AbstractScopeProviderTestCase
{
    public function testProviderRegisteredWithScopeTypes()
    {
        self::assertProviderRegisteredWithScopeTypes(
            ScopeAccountGroupCriteriaProvider::FIELD_NAME,
            [
                'account_group_category_visibility',
                'account_group_product_visibility',
                'workflow_definition',
                'web_content'
            ]
        );
    }
}
