<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ShippingBundle\Form\EventSubscriber\RuleConfigurationSubscriber;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleConfigurationCollectionType;

class ShippingRuleConfigurationCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var ShippingRuleConfigurationCollectionType */
    protected $formType;

    /** @var RuleConfigurationSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        parent::setUp();
        $this->subscriber = $this->getMockBuilder(RuleConfigurationSubscriber::class)
            ->disableOriginalConstructor()->getMock();
        $this->formType = new ShippingRuleConfigurationCollectionType($this->subscriber);
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingRuleConfigurationCollectionType::NAME, $this->formType->getName());
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
