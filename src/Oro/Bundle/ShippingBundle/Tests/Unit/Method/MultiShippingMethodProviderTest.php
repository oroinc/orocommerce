<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodProvider;
use PHPUnit\Framework\TestCase;

class MultiShippingMethodProviderTest extends TestCase
{
    public function testConstructor()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);

        new MultiShippingMethodProvider('multi_shipping_type', $doctrineHelper, $methodFactory);
    }
}
