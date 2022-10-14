<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryUpcomingFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class CategoryUpcomingFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryUpcomingFormExtension */
    private $categoryFormExtension;

    protected function setUp(): void
    {
        $this->categoryFormExtension = new CategoryUpcomingFormExtension();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnSelf();
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->willReturnSelf();

        $this->categoryFormExtension->buildForm($builder, []);
    }

    public function testOnPreSetData()
    {
        $category = new CategoryStub();
        $category->setParentCategory(new CategoryStub());

        $event = new FormEvent($this->createMock(FormInterface::class), $category);
        $this->categoryFormExtension->onPreSetData($event);

        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getIsUpcoming());
        $this->assertEquals(ParentCategoryFallbackProvider::FALLBACK_ID, $category->getIsUpcoming()->getFallback());
    }

    public function testOnPostSubmitDateUnchanged()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setScalarValue(1);

        $category = new CategoryStub();
        $category->setIsUpcoming($fallbackValue);
        $date = new \DateTime();
        $category->setAvailabilityDate($date);

        $event = new FormEvent($this->createMock(FormInterface::class), $category);
        $this->categoryFormExtension->onPostSubmit($event);

        $this->assertSame($date, $category->getAvailabilityDate());
    }

    public function testOnPostSubmit()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback(ParentCategoryFallbackProvider::FALLBACK_ID);

        $category = new CategoryStub();
        $category->setIsUpcoming($fallbackValue);
        $date = new \DateTime();
        $category->setAvailabilityDate($date);

        $event = new FormEvent($this->createMock(FormInterface::class), $category);
        $this->categoryFormExtension->onPostSubmit($event);

        $this->assertNull($category->getAvailabilityDate());
    }
}
