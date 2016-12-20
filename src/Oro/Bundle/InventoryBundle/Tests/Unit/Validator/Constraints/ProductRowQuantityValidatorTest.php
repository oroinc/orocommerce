<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductRowQuantity;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductRowQuantityValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\ProductRow;

class ProductRowQuantityValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuantityToOrderValidatorService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorService;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ProductRowQuantityValidator
     */
    protected $validator;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ProductRowQuantity
     */
    protected $constraint;

    protected function setUp()
    {
        $this->validatorService = $this->getMockBuilder(QuantityToOrderValidatorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->constraint = new ProductRowQuantity();
        $this->context = $this->getMock(ExecutionContextInterface::class);
        $this->validator = new ProductRowQuantityValidator(
            $this->validatorService,
            $this->doctrineHelper
        );
        $this->validator->initialize($this->context);
    }

    public function testValidateEmptyValue()
    {
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate(null, $this->constraint);
    }

    public function testValidateNoProductValue()
    {
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateNoProduct()
    {
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $repository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('findOneBySku')
            ->with($productRow->productSku)
            ->willReturn(null);
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate($productRow, $this->constraint);
    }

    public function testValidateQuantityNotNumeric()
    {
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 'string';
        $repository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('findOneBySku')
            ->with($productRow->productSku)
            ->willReturn(new Product());
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate($productRow, $this->constraint);
    }

    public function testValidateNoConstraint()
    {
        $product = new Product();
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 10;
        $repository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('findOneBySku')
            ->with($productRow->productSku)
            ->willReturn($product);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMinimumErrorIfInvalid')
            ->with($product)
            ->willReturn(false);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->with($product)
            ->willReturn(false);
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate($productRow, $this->constraint);
    }

    public function testValidateMaxLimitConstraint()
    {
        $product = new ProductStub();
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 10;
        $maxErrorMessage = 'maxMessage';
        $repository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('findOneBySku')
            ->with($productRow->productSku)
            ->willReturn($product);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->with($product)
            ->willReturn($maxErrorMessage);
        // should not be called as maximum validation is triggered
        $this
            ->validatorService
            ->expects($this->never())
            ->method('getMinimumErrorIfInvalid')
            ->with($product);
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($maxErrorMessage)
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->willReturn($this->getMock(ConstraintViolationBuilderInterface::class));
        $this->validator->validate($productRow, $this->constraint);
    }

    public function testValidateMinLimitConstraint()
    {
        $product = new ProductStub();
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 10;
        $minMessage = 'minMessage';
        $repository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository
            ->expects($this->once())
            ->method('findOneBySku')
            ->with($productRow->productSku)
            ->willReturn($product);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMinimumErrorIfInvalid')
            ->with($product)
            ->willReturn($minMessage);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->with($product)
            ->willReturn(false);

        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($minMessage)
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->willReturn($this->getMock(ConstraintViolationBuilderInterface::class));
        $this->validator->validate($productRow, $this->constraint);
    }
}
