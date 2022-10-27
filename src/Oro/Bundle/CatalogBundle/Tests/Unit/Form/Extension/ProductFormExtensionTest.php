<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Form\Extension\ProductFormExtension;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category as CategoryStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductFormExtensionTest extends TestCase
{
    private CategoryRepository|MockObject $categoryRepository;
    private ManagerRegistry|MockObject $registry;
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;
    private ProductFormExtension $extension;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->extension = new ProductFormExtension($this->registry, $this->authorizationChecker);
    }

    private function prepareRegistry(bool $expects = false): void
    {
        $this->registry->expects($expects ? $this->once() : $this->never())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([ProductType::class], ProductFormExtension::getExtendedTypes());
    }

    public function testBuildForm()
    {
        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(true);

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
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']],
                [FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10]
            );

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormWhenCatalogViewDisabledByAcl()
    {
        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(false);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('add');
        $builder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSetDataNoProduct()
    {
        $this->prepareRegistry();

        $event = $this->createEvent(null);

        $this->categoryRepository->expects($this->never())
            ->method('findOneByProduct');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataNewProduct()
    {
        $this->prepareRegistry();

        $event = $this->createEvent($this->createProduct());

        $this->categoryRepository->expects($this->never())
            ->method('findOneByProduct');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataExistingProduct()
    {
        $this->prepareRegistry(true);

        $product = $this->createProduct(1);
        $event = $this->createEvent($product);

        $category = $this->createCategory();

        $this->categoryRepository->expects($this->once())
            ->method('findOneByProduct')
            ->with($product)
            ->willReturn($category);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $categoryForm */
        $categoryForm = $event->getForm()->get('category');
        $categoryForm->expects($this->once())
            ->method('setData')
            ->with($category);

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitNoProduct()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->never())
            ->method('isValid');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createProduct());
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $categoryForm */
        $categoryForm = $mainForm->get('category');
        $categoryForm->expects($this->never())
            ->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNewProduct()
    {
        $this->prepareRegistry();

        $product = $this->createProduct();
        $event = $this->createEvent($product);

        $category = $this->createCategory(1);

        $this->assertCategoryAdd($event, $category);
        $this->categoryRepository->expects($this->never())
            ->method('findOneByProduct');

        $this->extension->onPostSubmit($event);

        $this->assertEquals([$product], $category->getProducts()->toArray());
    }

    public function testOnPostSubmitExistingProduct()
    {
        $this->prepareRegistry(true);

        $product = $this->createProduct(1);
        $event = $this->createEvent($product);

        $newCategory         = $this->createCategory(1);
        $categoryWithProduct = $this->createCategory(2);
        $categoryWithProduct->addProduct($product);

        $this->assertCategoryAdd($event, $newCategory);
        $this->categoryRepository->expects($this->once())
            ->method('findOneByProduct')
            ->willReturn($categoryWithProduct);

        $this->extension->onPostSubmit($event);

        $this->assertEquals([$product], $newCategory->getProducts()->toArray());
        $this->assertEquals([], $categoryWithProduct->getProducts()->toArray());
    }

    private function createEvent(?Product $data): FormEvent
    {
        $categoryForm = $this->createMock(FormInterface::class);

        $mainForm = $this->createMock(FormInterface::class);
        $mainForm->expects($this->any())
            ->method('get')
            ->with('category')
            ->willReturn($categoryForm);

        return new FormEvent($mainForm, $data);
    }

    private function createProduct(int $id = null): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function createCategory(int $id = null): Category
    {
        $category = new CategoryStub();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    private function assertCategoryAdd(FormEvent $event, Category $category): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $categoryForm */
        $categoryForm = $mainForm->get('category');
        $categoryForm->expects($this->once())
            ->method('getData')
            ->willReturn($category);
    }
}
