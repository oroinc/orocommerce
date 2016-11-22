<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleMethodTypeConfigCollectionSubscriber;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodTypeConfigCollectionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingRuleConfigurationCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var ShippingRuleMethodTypeConfigCollectionType */
    protected $formType;

    /** @var RuleMethodTypeConfigCollectionSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        parent::setUp();
        $this->subscriber = $this->getMockBuilder(RuleMethodTypeConfigCollectionSubscriber::class)
            ->disableOriginalConstructor()->getMock();
        $this->formType = new ShippingRuleMethodTypeConfigCollectionType($this->subscriber);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(
            ShippingRuleMethodTypeConfigCollectionType::BLOCK_PREFIX,
            $this->formType->getBlockPrefix()
        );
    }

    public function testBuildFormSubscriber()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber)
            ->willReturn($builder);
        $this->formType->buildForm($builder, []);
    }
}
