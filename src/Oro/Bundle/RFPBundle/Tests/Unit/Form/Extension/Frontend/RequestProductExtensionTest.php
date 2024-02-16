<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension\Frontend;

use Oro\Bundle\RFPBundle\Form\Extension\Frontend\RequestProductExtension;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class RequestProductExtensionTest extends TestCase
{
    private ResolvedProductVisibilityProvider $productVisibilityProvider;
    private RequestProductExtension $extension;

    protected function setUp(): void
    {
        $this->productVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);
        $this->extension = new RequestProductExtension($this->productVisibilityProvider);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit']);

        $this->extension->buildForm($builder, []);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([RequestProductType::class], RequestProductExtension::getExtendedTypes());
    }

    public function testOnPreSubmitEmptyFormData(): void
    {
        $event = $this->createFormEvent(null);
        $this->productVisibilityProvider->expects(self::never())
            ->method('isVisible');

        $this->extension->onPreSubmit($event);
        $this->assertEmpty($event->getData());
    }

    public function testOnPreSubmitEmptyArrayFormData(): void
    {
        $event = $this->createFormEvent([]);
        $this->productVisibilityProvider->expects(self::never())
            ->method('isVisible');

        $this->extension->onPreSubmit($event);
        $this->assertEmpty($event->getData());
    }

    public function testOnPreSubmitVisibleProduct(): void
    {
        $productId = 42;
        $event = $this->createFormEvent(['product' => $productId]);
        $this->productVisibilityProvider->expects(self::once())
            ->method('isVisible')
            ->with($productId)
            ->willReturn(true);

        $this->extension->onPreSubmit($event);
        $this->assertSame(['product' => $productId], $event->getData());
    }

    public function testOnPreSubmitHiddenProduct(): void
    {
        $productId = 42;
        $event = $this->createFormEvent(['product' => $productId]);
        $this->productVisibilityProvider->expects(self::once())
            ->method('isVisible')
            ->with($productId)
            ->willReturn(false);

        $this->extension->onPreSubmit($event);
        $this->assertSame(['product' => null], $event->getData());
    }

    private function createFormEvent(?array $data): FormEvent
    {
        $form = $this->createMock(FormInterface::class);

        return  new FormEvent($form, $data);
    }
}
