<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodProvider;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;

class FlatRateMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    const CHANNEL_TYPE = 'channel_type';

    /** @var \PHPUnit_Framework_MockObject_MockObject|IntegrationShippingMethodFactoryInterface */
    private $methodBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    private $doctrineHelper;

    public function setUp()
    {
        $this->methodBuilder = $this->createMock(IntegrationShippingMethodFactoryInterface::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
    }

    public function testConstructor()
    {
        new FlatRateMethodProvider(static::CHANNEL_TYPE, $this->doctrineHelper, $this->methodBuilder);
    }
}
