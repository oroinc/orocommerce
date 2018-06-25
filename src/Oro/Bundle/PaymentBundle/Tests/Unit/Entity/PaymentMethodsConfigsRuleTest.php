<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentMethodsConfigsRuleTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $properties = [
            ['id', '1'],
            ['rule', new Rule()],
            ['currency', 'USD'],
            ['organization', new Organization()],
        ];

        $entity = new PaymentMethodsConfigsRule();

        static::assertPropertyAccessors($entity, $properties);
        static::assertPropertyCollection($entity, 'methodConfigs', new PaymentMethodConfig());
        static::assertPropertyCollection($entity, 'destinations', new PaymentMethodsConfigsRuleDestination());
        static::assertPropertyCollection($entity, 'websites', new Website());
    }
}
