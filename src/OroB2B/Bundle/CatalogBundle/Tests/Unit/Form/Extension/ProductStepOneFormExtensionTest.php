<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Extension\ProductFormExtension;
use OroB2B\Bundle\CatalogBundle\Form\Extension\ProductStepOneFormExtension;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductStepOneType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductStepOneFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $defaultProductUnitProvider;

    /**
     * @var ProductFormExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->defaultProductUnitProvider = $this
            ->getMockBuilder('OroB2B\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ProductStepOneFormExtension($this->defaultProductUnitProvider);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ProductStepOneType::NAME, $this->extension->getExtendedType());
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
        $builder->expects($this->exactly(1))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit'], 10);

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSubmitNoCategory()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createCategory());
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

    public function testOnPostSubmitWithCategory()
    {
        $category = $this->createCategory(1);
        $event   = $this->createEvent($category);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $categoryForm */
        $categoryForm = $mainForm->get('category');
        $categoryForm->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $this->defaultProductUnitProvider->expects($this->once())
            ->method('setCategoryId')
            ->with($category->getId());

        $this->extension->onPostSubmit($event);
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
}
