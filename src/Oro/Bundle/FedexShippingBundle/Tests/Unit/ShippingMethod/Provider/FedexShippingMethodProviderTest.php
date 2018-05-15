<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Provider\FedexShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use PHPUnit\Framework\TestCase;

class FedexShippingMethodProviderTest extends TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodFactory;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
    }

    public function testConstructor()
    {
        new FedexShippingMethodProvider('type', $this->doctrineHelper, $this->methodFactory);
    }
}
