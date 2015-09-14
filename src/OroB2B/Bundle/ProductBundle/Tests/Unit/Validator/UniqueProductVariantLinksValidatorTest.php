<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;

use OroB2B\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;

class UniqueProductVariantLinksValidatorTest extends \PHPUnit_Framework_TestCase
{
    const VARIANT_FIELD_KEY_COLOR = 'color';
    const VARIANT_FIELD_KEY_SIZE = 'size';

    /**
     * @var UniqueProductVariantLinksValidator
     */
    protected $service;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->service = new UniqueProductVariantLinksValidator();
        $this->service->initialize($this->context);
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUnique()
    {
        $product = $this->prepareProduct([
            [
                self::VARIANT_FIELD_KEY_SIZE => 'L',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
            [
                self::VARIANT_FIELD_KEY_SIZE => 'M',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
        ]);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUnique()
    {
        $product = $this->prepareProduct([
            [
                self::VARIANT_FIELD_KEY_SIZE => 'L',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
            [
                self::VARIANT_FIELD_KEY_SIZE => 'L',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
        ]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->variantFieldValueCombinationsShouldBeUnique);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    private function prepareProduct(array $variantLinkFields)
    {
        $product = new StubProduct();
        $product->setHasVariants(true);
        $product->setVariantFields(array_keys($variantLinkFields[0]));

        foreach ($variantLinkFields as $fields) {
            $variantProduct = new StubProduct();
            $variantProduct->setSize($fields[self::VARIANT_FIELD_KEY_SIZE]);
            $variantProduct->setColor($fields[self::VARIANT_FIELD_KEY_COLOR]);

            $variantLink =  new ProductVariantLink($product, $variantProduct);
            $product->addVariantLink($variantLink);
        }

        return $product;
    }
}
