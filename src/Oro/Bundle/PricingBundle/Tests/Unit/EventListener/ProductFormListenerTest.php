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

    /** @var PriceManager|\PHPUnit\Framework\MockObject\MockObject */
    private $priceManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ProductFormListener */
    private $listener;

    protected function setUp(): void
    {
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ProductFormListener($this->priceManager);
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
        $event->expects($this->never())
            ->method('getData');

        $this->listener->onBeforeFlush($event);
    }

    public function testOnBeforeFlushWithNewProduct()
    {
        $form = $this->createMock(FormInterface::class);

        $this->priceManager->expects($this->never())
            ->method('flush');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onBeforeFlush(new AfterFormProcessEvent($form, new Product()));
    }

    public function testOnBeforeFlushWithSavedProduct()
    {
        $form = $this->createMock(FormInterface::class);
        $product = $this->getEntity(Product::class, ['id' => 5]);

        $this->priceManager->expects($this->once())
            ->method('flush');

        $this->listener->onBeforeFlush(new AfterFormProcessEvent($form, $product));
    }
}
