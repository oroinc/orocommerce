<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Bundle\SEOBundle\EventListener\ProductDuplicateListener;
use Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub\ProductStub as Product;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductDuplicateListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductDuplicateListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new ProductDuplicateListener($this->getPropertyAccessor(), [
            'metaTitles',
            'metaDescriptions',
            'metaKeywords',
        ]);
        $this->listener->setDoctrineHelper($this->doctrineHelper);
    }

    public function testOnDuplicateAfter()
    {
        $sourceProduct = $this->getEntity(Product::class, [
            'id' => 1,
            'metaTitles' => new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => 1, 'string' => 'defaultMetaTitle']),
                $this->getLocalizedFallbackValue('en', ['id' => 2, 'string' => 'enMetaTitle']),
                $this->getLocalizedFallbackValue('de', ['id' => 3, 'string' => 'deMetaTitle']),
            ]),
            'metaDescriptions' => new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => 4, 'text' => 'defaultMetaDescription']),
                $this->getLocalizedFallbackValue('en', ['id' => 5, 'text' => 'enMetaDescription']),
                $this->getLocalizedFallbackValue('de', ['id' => 6, 'text' => 'deMetaDescription']),
            ]),
            'metaKeywords' => new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => 7, 'text' => 'defaultMetaKeywords']),
                $this->getLocalizedFallbackValue('en', ['id' => 8, 'text' => 'enMetaKeywords']),
                $this->getLocalizedFallbackValue('de', ['id' => 9, 'text' => 'deMetaKeywords']),
            ]),
        ]);

        $product = $this->getEntity(Product::class, ['id' => 2]);
        $expectedProduct = $this->getEntity(Product::class, [
            'id' => 2,
            'metaTitles' => new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => null, 'string' => 'defaultMetaTitle']),
                $this->getLocalizedFallbackValue('en', ['id' => null, 'string' => 'enMetaTitle']),
                $this->getLocalizedFallbackValue('de', ['id' => null, 'string' => 'deMetaTitle']),
            ]),
            'metaDescriptions' => new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => null, 'text' => 'defaultMetaDescription']),
                $this->getLocalizedFallbackValue('en', ['id' => null, 'text' => 'enMetaDescription']),
                $this->getLocalizedFallbackValue('de', ['id' => null, 'text' => 'deMetaDescription']),
            ]),
            'metaKeywords' => new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => null, 'text' => 'defaultMetaKeywords']),
                $this->getLocalizedFallbackValue('en', ['id' => null, 'text' => 'enMetaKeywords']),
                $this->getLocalizedFallbackValue('de', ['id' => null, 'text' => 'deMetaKeywords']),
            ]),
        ]);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('flush')
            ->with($expectedProduct);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($em);

        $event = new ProductDuplicateAfterEvent($product, $sourceProduct);
        $this->listener->onDuplicateAfter($event);

        $this->assertEquals(
            $this->getPropertyAccessor()->getValue($product, 'metaTitles'),
            new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => null, 'string' => 'defaultMetaTitle']),
                $this->getLocalizedFallbackValue('en', ['id' => null, 'string' => 'enMetaTitle']),
                $this->getLocalizedFallbackValue('de', ['id' => null, 'string' => 'deMetaTitle']),
            ])
        );

        $this->assertEquals(
            $this->getPropertyAccessor()->getValue($product, 'metaDescriptions'),
            new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => null, 'text' => 'defaultMetaDescription']),
                $this->getLocalizedFallbackValue('en', ['id' => null, 'text' => 'enMetaDescription']),
                $this->getLocalizedFallbackValue('de', ['id' => null, 'text' => 'deMetaDescription']),
            ])
        );

        $this->assertEquals(
            $this->getPropertyAccessor()->getValue($product, 'metaKeywords'),
            new ArrayCollection([
                $this->getLocalizedFallbackValue(null, ['id' => null, 'text' => 'defaultMetaKeywords']),
                $this->getLocalizedFallbackValue('en', ['id' => null, 'text' => 'enMetaKeywords']),
                $this->getLocalizedFallbackValue('de', ['id' => null, 'text' => 'deMetaKeywords']),
            ])
        );
    }

    public function testOnDuplicateAfterWithoutMeta()
    {
        $sourceProduct = $this->getEntity(Product::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $expectedProduct = $this->getEntity(Product::class, [
            'id' => 2,
            'metaTitles' => new ArrayCollection(),
            'metaDescriptions' => new ArrayCollection(),
            'metaKeywords' => new ArrayCollection(),
        ]);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('flush')
            ->with($expectedProduct);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($em);

        $event = new ProductDuplicateAfterEvent($product, $sourceProduct);
        $this->listener->onDuplicateAfter($event);

        $this->assertEquals(
            $this->getPropertyAccessor()->getValue($product, 'metaTitles'),
            new ArrayCollection()
        );

        $this->assertEquals(
            $this->getPropertyAccessor()->getValue($product, 'metaDescriptions'),
            new ArrayCollection()
        );

        $this->assertEquals(
            $this->getPropertyAccessor()->getValue($product, 'metaKeywords'),
            new ArrayCollection()
        );
    }

    /**
     * @param $locale
     * @param array $properties
     *
     * @return LocalizedFallbackValue
     */
    private function getLocalizedFallbackValue($locale, array $properties = [])
    {
        $fallbackValue = $this->getEntity(LocalizedFallbackValue::class, $properties);
        if ($locale) {
            $localization = new Localization();
            $localization->setName($locale);
            $localization->setLanguage($locale);
            $localization->setFormattingCode($locale);

            $fallbackValue->setLocalization($localization);
        }

        return $fallbackValue;
    }
}
