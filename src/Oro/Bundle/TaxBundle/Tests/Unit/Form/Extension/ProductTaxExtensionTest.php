<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Extension\AbstractTaxExtension;
use Oro\Bundle\TaxBundle\Form\Extension\ProductTaxExtension;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class ProductTaxExtensionTest extends AbstractTaxExtensionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getExtension(): AbstractTaxExtension
    {
        return new ProductTaxExtension();
    }

    /**
     * {@inheritDoc}
     */
    protected function createTaxCodeTarget(int $id = null): object
    {
        $entity = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['setTaxCode', 'getTaxCode'])
            ->getMock();
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    protected function createTaxCode(int $id = null): AbstractTaxCode
    {
        $taxCode = new ProductTaxCode();
        if (null !== $id) {
            ReflectionUtil::setId($taxCode, $id);
        }

        return $taxCode;
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([ProductType::class], ProductTaxExtension::getExtendedTypes());
    }

    public function testBuildForm()
    {
        $productTaxExtension = $this->getExtension();

        $builder = $this->createMock(FormBuilderInterface::class);
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
                    'dynamic_fields_ignore_exception' => true,
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::POST_SET_DATA, [$productTaxExtension, 'onPostSetData']],
                [FormEvents::POST_SUBMIT, [$productTaxExtension, 'onPostSubmit'], 10]
            );

        $productTaxExtension->buildForm($builder, []);
    }

    public function testOnPostSetDataExistingProduct()
    {
        $product = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($product);
        $taxCode = $this->createTaxCode();

        $product->expects($this->any())
            ->method('getTaxCode')
            ->willReturn($taxCode);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $taxCodeForm */
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
        $product->expects($this->once())
            ->method('setTaxCode');

        $product->expects($this->any())
            ->method('getTaxCode')
            ->willReturn($taxCode);
        $this->assertTaxCodeAdd($event, $taxCode);

        $this->getExtension()->onPostSubmit($event);
    }

    public function testOnPostSubmitExistingProduct()
    {
        $product = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($product);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithProduct = $this->createTaxCode(2);
        $product->expects($this->any())
            ->method('getTaxCode')
            ->willReturn($taxCodeWithProduct);
        $product->expects($this->once())
            ->method('setTaxCode');
        $this->assertTaxCodeAdd($event, $newTaxCode);

        $this->getExtension()->onPostSubmit($event);
    }
}
