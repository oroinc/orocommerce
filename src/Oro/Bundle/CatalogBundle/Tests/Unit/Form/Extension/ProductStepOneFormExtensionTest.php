<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Extension\ProductFormExtension;
use Oro\Bundle\CatalogBundle\Form\Extension\ProductStepOneFormExtension;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class ProductStepOneFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryDefaultProductUnitProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultProductUnitProvider;

    /** @var ProductFormExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->defaultProductUnitProvider = $this->createMock(CategoryDefaultProductUnitProvider::class);

        $this->extension = new ProductStepOneFormExtension($this->defaultProductUnitProvider);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ProductStepOneType::class], ProductStepOneFormExtension::getExtendedTypes());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'category',
                CategoryTreeType::class,
                [
                    'required' => false,
                    'mapped'   => false,
                    'label'    => 'oro.catalog.category.entity_label'
                ]
            );
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSubmitNoCategory()
    {
        $mainForm = $this->createMock(FormInterface::class);
        $categoryForm = $this->createMock(FormInterface::class);
        $mainForm->expects($this->any())
            ->method('get')
            ->with('category')
            ->willReturn($categoryForm);
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->defaultProductUnitProvider->expects($this->never())
            ->method($this->anything());

        $this->extension->onPostSubmit(new FormEvent($mainForm, null));
    }

    public function testOnPostSubmitInvalidForm()
    {
        $mainForm = $this->createMock(FormInterface::class);
        $categoryForm = $this->createMock(FormInterface::class);
        $mainForm->expects($this->any())
            ->method('get')
            ->with('category')
            ->willReturn($categoryForm);
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $categoryForm->expects($this->never())
            ->method('getData');

        $this->defaultProductUnitProvider->expects($this->never())
            ->method($this->anything());

        $this->extension->onPostSubmit(new FormEvent($mainForm, new Category()));
    }

    public function testOnPostSubmitWithCategory()
    {
        $category = new Category();
        ReflectionUtil::setId($category, 1);

        $mainForm = $this->createMock(FormInterface::class);
        $categoryForm = $this->createMock(FormInterface::class);
        $mainForm->expects($this->any())
            ->method('get')
            ->with('category')
            ->willReturn($categoryForm);
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $categoryForm->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $this->defaultProductUnitProvider->expects($this->once())
            ->method('setCategory')
            ->with($category);

        $this->extension->onPostSubmit(new FormEvent($mainForm, $category));
    }
}
