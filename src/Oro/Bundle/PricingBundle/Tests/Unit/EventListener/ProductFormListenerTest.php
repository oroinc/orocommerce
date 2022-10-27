<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\EventListener\ProductFormListener;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class ProductFormListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var PriceManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceManager;

    /**
     * @var ProductFormListener
     */
    private $listener;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    protected function setUp(): void
    {
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->listener = new ProductFormListener($this->priceManager);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
    }

    protected function tearDown(): void
    {
        unset($this->priceManager, $this->listener, $this->featureChecker);
    }

    public function testOnBeforeFlushFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $event = $this->createMock(AfterFormProcessEvent::class);
        $event->expects($this->never())->method('getData');

        $this->listener->onBeforeFlush($event);
    }

    public function testOnBeforeFlushWithNewProduct()
    {
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        /** @var AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $event **/
        $event = new AfterFormProcessEvent($form, new Product());

        $this->priceManager
            ->expects($this->never())
            ->method('flush');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onBeforeFlush($event);
    }

    public function testOnBeforeFlushWithSavedProduct()
    {
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $product = $this->getEntity(Product::class, ['id' => 5]);

        /** @var AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $event **/
        $event = new AfterFormProcessEvent($form, $product);

        $this->priceManager
            ->expects($this->once())
            ->method('flush');

        $this->listener->onBeforeFlush($event);
    }
}
