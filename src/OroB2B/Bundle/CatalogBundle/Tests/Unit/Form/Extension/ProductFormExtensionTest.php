<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use OroB2B\Bundle\CatalogBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryRepository;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProductFormExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->extension = new ProductFormExtension($this->registry);
    }

    /**
     * @param bool $expects
     */
    protected function prepareRegistry($expects = false)
    {
        $this->categoryRepository =
            $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $entityManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $entityManager->expects($expects ? $this->once() : $this->never())
            ->method('getRepository')
            ->with('OroB2BCatalogBundle:Category')
            ->willReturn($this->categoryRepository);

        $this->registry->expects($expects ? $this->once() : $this->never())
            ->method('getManagerForClass')
            ->with('OroB2BCatalogBundle:Category')
            ->willReturn($entityManager);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ProductType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'category',
                CategoryTreeType::NAME,
                [
                    'required' => false,
                    'mapped'   => false,
                    'label'    => 'orob2b.catalog.category.entity_label'
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

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

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $categoryForm */
        $categoryForm = $event->getForm()->get('category');
        $categoryForm->expects($this->once())
            ->method('setData')
            ->with($category);

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSubmitNoProduct()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->never())
            ->method('isValid');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createProduct());
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $categoryForm */
        $categoryForm = $mainForm->get('category');
        $categoryForm->expects($this->never())
            ->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNewProduct()
    {
        $this->prepareRegistry();

        $product = $this->createProduct();
        $event   = $this->createEvent($product);

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
        $event   = $this->createEvent($product);

        $newCategory         = $this->createCategory(1);
        $categoryWithProduct = $this->createCategory(2);
        $categoryWithProduct->addProduct($product);

        $this->assertCategoryAdd($event, $newCategory);
        $this->categoryRepository->expects($this->once())
            ->method('findOneByProduct')
            ->will($this->returnValue($categoryWithProduct));

        $this->extension->onPostSubmit($event);

        $this->assertEquals([$product], $newCategory->getProducts()->toArray());
        $this->assertEquals([], $categoryWithProduct->getProducts()->toArray());
    }

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $categoryForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('category')
            ->willReturn($categoryForm);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param int|null $id
     *
     * @return Product
     */
    protected function createProduct($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $id);
    }

    /**
     * @param int|null $id
     *
     * @return Category
     */
    protected function createCategory($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', $id);
    }

    /**
     * @param          $class string
     * @param int|null $id
     *
     * @return object
     */
    protected function createEntity($class, $id = null)
    {
        $entity = new $class();
        if ($id) {
            $reflection = new \ReflectionProperty($class, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($entity, $id);
        }

        return $entity;
    }

    /**
     * @param FormEvent $event
     * @param Category  $category
     */
    protected function assertCategoryAdd(FormEvent $event, Category $category)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $categoryForm */
        $categoryForm = $mainForm->get('category');
        $categoryForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($category));
    }
}
