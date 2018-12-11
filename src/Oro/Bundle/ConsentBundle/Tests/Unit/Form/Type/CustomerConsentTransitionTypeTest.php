<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CheckoutCustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentTransitionType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerConsentTransitionTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomerConsentTransitionType
     */
    protected $formType;

    /**
     * @var CheckoutCustomerConsentsEventSubscriber|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->subscriber = $this->createMock(CheckoutCustomerConsentsEventSubscriber::class);

        $this->formType = new CustomerConsentTransitionType($this->subscriber);
    }

    public function testBuildFormFeatureDisabled()
    {
        $this->formType->addFeature('feature');
        $featureChecker = $this->createMock(FeatureChecker::class);
        $this->formType->setFeatureChecker($featureChecker);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature', null)
            ->willReturn(false);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('addEventSubscriber');

        $this->formType->buildForm($builder, []);
    }

    public function testBuildForm()
    {
        $this->formType->addFeature('feature');
        $featureChecker = $this->createMock(FeatureChecker::class);
        $this->formType->setFeatureChecker($featureChecker);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature', null)
            ->willReturn(true);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->subscriber);

        $this->formType->buildForm($builder, []);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_customer_consents_transition_type', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(WorkflowTransitionType::class, $this->formType->getParent());
    }
}
