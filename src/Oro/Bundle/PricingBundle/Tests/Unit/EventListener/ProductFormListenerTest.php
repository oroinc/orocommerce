<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\EventListener\ProductFormListener;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class ProductFormListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceManager;

    /**
     * @var ProductFormListener
     */
    private $listener;

    protected function setUp()
    {
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->listener = new ProductFormListener($this->priceManager);
    }

    public function testOnBeforeFlushWithNewProduct()
    {
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        /** @var AfterFormProcessEvent|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = new AfterFormProcessEvent($form, new Product());

        $this->priceManager
            ->expects($this->never())
            ->method('flush');

        $this->listener->onBeforeFlush($event);
    }

    public function testOnBeforeFlushWithSavedProduct()
    {
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $product = $this->getEntity(Product::class, ['id' => 5]);

        /** @var AfterFormProcessEvent|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = new AfterFormProcessEvent($form, $product);

        $this->priceManager
            ->expects($this->once())
            ->method('flush');

        $this->listener->onBeforeFlush($event);
    }
}
