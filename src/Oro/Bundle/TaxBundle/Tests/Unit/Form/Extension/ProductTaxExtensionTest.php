<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\TaxBundle\Form\Extension\ProductTaxExtension;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

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
        $this->assertEquals(ProductType::class, $this->getExtension()->getExtendedType());
    }

    public function testBuildForm()
    {
        $productTaxExtension = $this->getExtension();

        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'taxCode',
                ProductTaxCodeAutocompleteType::class,
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
        $taxCode = $this->createTaxCode();
        $product = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($product);
        $product->method('getTaxCode')->willReturn($taxCode);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $event->getForm()->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('setData')
            ->with($taxCode);

        $this->getExtension()->onPostSetData($event);
    }

    public function testOnPostSubmitNewProduct()
    {
        $product = $this->createTaxCodeTarget();
        $event = $this->createEvent($product);
        $taxCode = $this->createTaxCode(1);
        $product->expects($this->once())->method('setTaxCode');

        $product->method('getTaxCode')->willReturn($taxCode);
        $this->assertTaxCodeAdd($event, $taxCode);

        $this->getExtension()->onPostSubmit($event);
    }

    public function testOnPostSubmitExistingProduct()
    {
        $product = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($product);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithProduct = $this->createTaxCode(2);
        $product->method('getTaxCode')->willReturn($taxCodeWithProduct);
        $product->expects($this->once())->method('setTaxCode');
        $this->assertTaxCodeAdd($event, $newTaxCode);

        $this->getExtension()->onPostSubmit($event);
    }

    /**
     * @param int|null $id
     *
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createTaxCodeTarget($id = null)
    {
        $mock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'setTaxCode', 'getTaxCode'])
            ->getMock();
        $mock->method('getId')->willReturn($id);

        return $mock;
    }

    /**
     * @param int|null $id
     *
     * @return ProductTaxCode
     */
    protected function createTaxCode($id = null)
    {
        return $this->getEntity(ProductTaxCode::class, ['id' => $id]);
    }
}
