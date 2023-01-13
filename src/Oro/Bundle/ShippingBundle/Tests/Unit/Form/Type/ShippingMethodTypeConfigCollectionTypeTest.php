<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodTypeConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodTypeConfigCollectionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingMethodTypeConfigCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var MethodTypeConfigCollectionSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $subscriber;

    /** @var ShippingMethodTypeConfigCollectionType */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = $this->createMock(MethodTypeConfigCollectionSubscriber::class);
        $this->formType = new ShippingMethodTypeConfigCollectionType($this->subscriber);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(
            ShippingMethodTypeConfigCollectionType::BLOCK_PREFIX,
            $this->formType->getBlockPrefix()
        );
    }

    public function testBuildFormSubscriber()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $this->formType->buildForm($builder, []);
    }
}
