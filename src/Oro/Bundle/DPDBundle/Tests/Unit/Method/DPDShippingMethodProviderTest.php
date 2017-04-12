<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method;

use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;

class DPDShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
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
        new DPDShippingMethodProvider(static::CHANNEL_TYPE, $this->doctrineHelper, $this->methodBuilder);
    }
}
