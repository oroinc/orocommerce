<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Traits\FormViewListenerWrongProductTestTrait;
use Oro\Bundle\SEOBundle\EventListener\ProductFormViewListener;

class ProductFormViewListenerTest extends BaseFormViewListenerTestCase
{
    use FormViewListenerWrongProductTestTrait;

    /** @var ProductFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new ProductFormViewListener($this->requestStack, $this->translator, $this->doctrineHelper);
    }

    protected function terDown()
    {
        unset($this->listener);

        parent::tearDown();
    }

    public function testOnProductView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $product = new Product();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getEnvironmentForView($product, $this->listener->getMetaFieldLabelPrefix());
        $event = $this->getEventForView($env);

        $this->listener->onProductView($event);
    }

    public function testOnProductEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $event = $this->getEventForEdit($env);

        $this->listener->onProductEdit($event);
    }
}
