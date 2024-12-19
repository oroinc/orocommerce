<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CheckoutBundle\Form\Extension\CheckoutWorkflowCsrfBCExtension;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CheckoutWorkflowCsrfBCExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const string CSRF_FIELD_NAME = 'token_field';

    /** @var CheckoutWorkflowCsrfBCExtension */
    private $checkoutWorkflowCsrfBCExtension;

    protected function setUp(): void
    {
        $this->checkoutWorkflowCsrfBCExtension = new CheckoutWorkflowCsrfBCExtension(self::CSRF_FIELD_NAME);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([WorkflowTransitionType::class], CheckoutWorkflowCsrfBCExtension::getExtendedTypes());
    }

    public function testFinishView(): void
    {
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->checkoutWorkflowCsrfBCExtension->finishView($formView, $form, $options);

        $this->assertArrayHasKey(self::CSRF_FIELD_NAME, $formView->children);
    }

    public function testFinishViewWithEnabledCsrf(): void
    {
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = ['csrf_protection' => true];

        $this->checkoutWorkflowCsrfBCExtension->finishView($formView, $form, $options);

        $this->assertArrayNotHasKey(self::CSRF_FIELD_NAME, $formView->children);
    }

    public function testFinishViewWithCsrfField(): void
    {
        $formView = new FormView();
        $formView->children[self::CSRF_FIELD_NAME] = true;
        $form = $this->createMock(FormInterface::class);
        $options = ['csrf_protection' => false];

        $this->checkoutWorkflowCsrfBCExtension->finishView($formView, $form, $options);

        $this->assertArrayHasKey(self::CSRF_FIELD_NAME, $formView->children);
        $this->assertTrue($formView->children[self::CSRF_FIELD_NAME]);
    }
}
