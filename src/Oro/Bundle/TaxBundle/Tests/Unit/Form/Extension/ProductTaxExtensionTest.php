<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\TaxBundle\Form\Extension\ProductTaxExtension;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;

class ProductTaxExtensionTest extends AbstractTaxExtensionTest
{
    /**
     * @var ProductTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var ProductTaxExtension
     */
    protected $extension;

    /**
     * @return ProductTaxExtension
     */
    protected function getExtension()
    {
        return new ProductTaxExtension($this->doctrineHelper, 'OroTaxBundle:ProductTaxCode');
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ProductType::NAME, $this->getExtension()->getExtendedType());
    }

    /**
     * @param bool $expectsManager
     * @param bool $expectsRepository
     */
    protected function prepareDoctrineHelper($expectsManager = false, $expectsRepository = false)
    {
        $this->entityRepository = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($expectsRepository ? $this->once() : $this->never())
            ->method('getEntityRepository')
            ->with('OroTaxBundle:ProductTaxCode')
            ->willReturn($this->entityRepository);
    }

    public function testBuildForm()
    {
        $productTaxExtension = $this->getExtension();

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
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$productTaxExtension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$productTaxExtension, 'onPostSubmit'], 10);

        $productTaxExtension->buildForm($builder, []);
    }

    public function testOnPostSetDataExistingProduct()
    {
        $this->prepareDoctrineHelper(false, true);

        $product = $this->createTaxCodeTarget(1);
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

        $this->getExtension()->onPostSetData($event);
    }

    public function testOnPostSubmitNewProduct()
    {
        $this->prepareDoctrineHelper(true, true);

        $product = $this->createTaxCodeTarget();
        $event = $this->createEvent($product);

        $taxCode = $this->createTaxCode(1);

        $this->assertTaxCodeAdd($event, $taxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByProduct');

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$product], $taxCode->getProducts()->toArray());
    }

    public function testOnPostSubmitExistingProduct()
    {
        $this->prepareDoctrineHelper(true, true);

        $product = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($product);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithProduct = $this->createTaxCode(2);
        $taxCodeWithProduct->addProduct($product);

        $this->assertTaxCodeAdd($event, $newTaxCode);
        $this->entityRepository->expects($this->once())
            ->method('findOneByProduct')
            ->will($this->returnValue($taxCodeWithProduct));

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$product], $newTaxCode->getProducts()->toArray());
        $this->assertEquals([], $taxCodeWithProduct->getProducts()->toArray());
    }

    /**
     * @param int|null $id
     *
     * @return Product
     */
    protected function createTaxCodeTarget($id = null)
    {
        return $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => $id]);
    }

    /**
     * @param int|null $id
     *
     * @return ProductTaxCode
     */
    protected function createTaxCode($id = null)
    {
        return $this->getEntity('Oro\Bundle\TaxBundle\Entity\ProductTaxCode', ['id' => $id]);
    }
}
