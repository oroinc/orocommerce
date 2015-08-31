<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
        $product = $this->getMock('OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct');

        $product->expects($this->once())
            ->method('getVariantFields')
            ->willReturn(array_keys($variantLinkFields[0]));

        $variantLinks = [];

        foreach ($variantLinkFields as $fields) {
            $variantLink = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink')
                ->disableOriginalConstructor()
                ->getMock();
            $variantProduct = $this->getMock('OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct');

            $variantLink->expects($this->once())
                ->method('getProduct')
                ->willReturn($variantProduct);

            $variantProduct->expects($this->once())
                ->method('getSize')
                ->willReturn($fields[self::VARIANT_FIELD_KEY_SIZE]);
            $variantProduct->expects($this->once())
                ->method('getColor')
                ->willReturn($fields[self::VARIANT_FIELD_KEY_COLOR]);

            $variantLinks[] = $variantLink;
        }

        $product->expects($this->once())
            ->method('getVariantLinks')
            ->willReturn($variantLinks);

        return $product;
    }
}
