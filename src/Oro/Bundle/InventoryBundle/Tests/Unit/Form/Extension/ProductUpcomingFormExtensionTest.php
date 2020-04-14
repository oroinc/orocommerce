<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductUpcomingFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class ProductUpcomingFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductUpcomingFormExtension
     */
    protected $productFormExtension;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder * */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productFormExtension = new ProductUpcomingFormExtension();
        $this->builder = $this->createMock(FormBuilderInterface::class);
    }

    public function testBuildForm()
    {
        $this->builder->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnSelf());
        $this->builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->will($this->returnSelf());

        $this->productFormExtension->buildForm($this->builder, []);
    }

    public function testOnPreSetData()
    {
        $product = new ProductStub();

        $event = new FormEvent($this->createMock(FormInterface::class), $product);
        $this->productFormExtension->onPreSetData($event);

        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getIsUpcoming());
        $this->assertEquals(CategoryFallbackProvider::FALLBACK_ID, $product->getIsUpcoming()->getFallback());
    }

    public function testOnPostSubmitDateUnchanged()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setScalarValue(1);

        $product = new ProductStub();
        $product->setIsUpcoming($fallbackValue);
        $date = new \DateTime();
        $product->setAvailabilityDate($date);

        $event = new FormEvent($this->createMock(FormInterface::class), $product);
        $this->productFormExtension->onPostSubmit($event);

        $this->assertSame($date, $product->getAvailabilityDate());
    }

    public function testOnPostSubmit()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback(CategoryFallbackProvider::FALLBACK_ID);

        $product = new ProductStub();
        $product->setIsUpcoming($fallbackValue);
        $date = new \DateTime();
        $product->setAvailabilityDate($date);

        $event = new FormEvent($this->createMock(FormInterface::class), $product);
        $this->productFormExtension->onPostSubmit($event);

        $this->assertNull($product->getAvailabilityDate());
    }
}
