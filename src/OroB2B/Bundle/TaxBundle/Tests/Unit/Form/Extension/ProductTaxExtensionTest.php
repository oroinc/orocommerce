<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use OroB2B\Bundle\TaxBundle\Form\Extension\ProductTaxExtension;

class ProductTaxExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ProductTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var ProductTaxExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ProductTaxExtension($this->doctrineHelper);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ProductType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @param bool $expectsManager
     * @param bool $expectsRepository
     */
    protected function prepareDoctrineHelper($expectsManager = false, $expectsRepository = false)
    {
        $entityManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $entityManager->expects($expectsManager ? $this->once() : $this->never())
            ->method('flush');

        $this->doctrineHelper->expects($expectsManager ? $this->once() : $this->never())
            ->method('getEntityManager')
            ->with('OroB2BTaxBundle:ProductTaxCode')
            ->willReturn($entityManager);

        $this->entityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($expectsRepository ? $this->once() : $this->never())
            ->method('getEntityRepository')
            ->with('OroB2BTaxBundle:ProductTaxCode')
            ->willReturn($this->entityRepository);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'taxCode',
                ProductTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.tax.producttaxcode.entity_label'
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
        $this->prepareDoctrineHelper();

        $event = $this->createEvent(null);

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataNewProduct()
    {
        $this->prepareDoctrineHelper();

        $event = $this->createEvent($this->createProduct());

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataExistingProduct()
    {
        $this->prepareDoctrineHelper(false, true);

        $product = $this->createProduct(1);
        $event = $this->createEvent($product);

        $taxCode = $this->createTaxCode();

        $this->entityRepository->expects($this->once())
            ->method('findOneByProduct')
            ->with($product)
            ->willReturn($taxCode);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $event->getForm()->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('setData')
            ->with($taxCode);

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

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->never())
            ->method('getData');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitNewProduct()
    {
        $this->prepareDoctrineHelper(true, true);

        $product = $this->createProduct();
        $event   = $this->createEvent($product);

        $taxCode = $this->createTaxCode(1);

        $this->assertTaxCodeAdd($event, $taxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByProduct');

        $this->extension->onPostSubmit($event);

        $this->assertEquals([$product], $taxCode->getProducts()->toArray());
    }

    public function testOnPostSubmitExistingProduct()
    {
        $this->prepareDoctrineHelper(true, true);

        $product = $this->createProduct(1);
        $event   = $this->createEvent($product);

        $newTaxCode         = $this->createTaxCode(1);
        $taxCodeWithProduct = $this->createTaxCode(2);
        $taxCodeWithProduct->addProduct($product);

        $this->assertTaxCodeAdd($event, $newTaxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByProduct')
            ->will($this->returnValue($taxCodeWithProduct));

        $this->extension->onPostSubmit($event);

        $this->assertEquals([$product], $newTaxCode->getProducts()->toArray());
        $this->assertEquals([], $taxCodeWithProduct->getProducts()->toArray());
    }

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $taxCodeForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('taxCode')
            ->willReturn($taxCodeForm);

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
     * @return ProductTaxCode
     */
    protected function createTaxCode($id = null)
    {
        return $this->createEntity('OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode', $id);
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
     * @param ProductTaxCode  $taxCode
     */
    protected function assertTaxCodeAdd(FormEvent $event, ProductTaxCode $taxCode)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($taxCode));
    }
}
