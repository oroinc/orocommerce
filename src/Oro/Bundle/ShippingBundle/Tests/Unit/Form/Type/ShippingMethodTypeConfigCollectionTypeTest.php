<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodTypeConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodTypeConfigCollectionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingMethodTypeConfigCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var ShippingMethodTypeConfigCollectionType */
    protected $formType;

    /** @var MethodTypeConfigCollectionSubscriber */
    protected $subscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = $this->getMockBuilder(MethodTypeConfigCollectionSubscriber::class)
            ->disableOriginalConstructor()->getMock();
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
