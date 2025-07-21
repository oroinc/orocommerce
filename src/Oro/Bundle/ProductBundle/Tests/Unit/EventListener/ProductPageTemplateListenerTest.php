<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\EventListener\ProductPageTemplateListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProductPageTemplateListenerTest extends TestCase
{
    private ProductPageTemplateListener $listener;
    private LifecycleEventArgs&MockObject $args;

    protected function setUp(): void
    {
        $this->listener = new ProductPageTemplateListener();
        $this->args = $this->createMock(LifecycleEventArgs::class);
    }

    public function testPrePersistSetsPageTemplateIfNotSet(): void
    {
        $product = new ProductStub();
        $this->listener->prePersist($product, $this->args);

        $pageTemplate = $product->getPageTemplate();
        self::assertInstanceOf(EntityFieldFallbackValue::class, $pageTemplate);
        self::assertSame(ThemeConfigurationFallbackProvider::FALLBACK_ID, $pageTemplate->getFallback());
    }

    public function testPrePersistDoesNotOverwriteExistingPageTemplate(): void
    {
        $product = new ProductStub();
        $existingFallback = new EntityFieldFallbackValue();
        $existingFallback->setFallback('custom_fallback');
        $product->setPageTemplate($existingFallback);

        $this->listener->prePersist($product, $this->args);

        self::assertSame($existingFallback, $product->getPageTemplate());
        self::assertSame('custom_fallback', $product->getPageTemplate()->getFallback());
    }
}
