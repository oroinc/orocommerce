<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinkByProductSkuValidator;
use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinksByProductSku;

class ProductVariantLinkByProductSkuValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductVariantLinkByProductSkuValidator */
    protected $service;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->service = new ProductVariantLinkByProductSkuValidator();
        $this->service->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->context, $this->service);
    }


    public function testDoesNothingIfIssetProduct()
    {
        $productVariantLink = new ProductVariantLink();
        $product = new StubProduct();
        $productVariantLink->setProduct($product);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($productVariantLink, new ProductVariantLinksByProductSku());
    }

    public function testAddViolationIfProductEmpty()
    {
        $productVariantLink = new ProductVariantLink();

        $this->context->expects($this->once())->method('addViolation');

        $this->service->validate($productVariantLink, new ProductVariantLinksByProductSku());
    }
}
