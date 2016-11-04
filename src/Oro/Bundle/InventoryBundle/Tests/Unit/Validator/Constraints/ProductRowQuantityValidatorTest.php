<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Translation\TranslatorInterface;
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
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

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
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->constraint = new ProductRowQuantity();
        $this->context = $this->getMock(ExecutionContextInterface::class);
        $this->validator = new ProductRowQuantityValidator(
            $this->validatorService,
            $this->doctrineHelper,
            $this->translator
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
            ->method('getMinimumLimit')
            ->with($product)
            ->willReturn(1);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMaximumLimit')
            ->with($product)
            ->willReturn(100);
        $this
            ->validatorService
            ->expects($this->exactly(1))
            ->method('isHigherThanMaxLimit')
            ->withConsecutive([100, $productRow->productQuantity], [1, $productRow->productQuantity])
            ->willReturnOnConsecutiveCalls(false, false);
        $this->context->expects($this->never())->method('addViolation');
        $this->validator->validate($productRow, $this->constraint);
    }

    public function testValidateMaxLimitConstraint()
    {
        $product = new ProductStub();
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
            ->method('getMinimumLimit')
            ->with($product)
            ->willReturn(1);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMaximumLimit')
            ->with($product)
            ->willReturn(100);
        $this
            ->validatorService
            ->expects($this->exactly(1))
            ->method('isHigherThanMaxLimit')
            ->with(100, $productRow->productQuantity)
            ->willReturn(true);
        $this
            ->validatorService
            ->expects($this->exactly(1))
            ->method('isLowerThenMinLimit')
            ->with(1, $productRow->productQuantity)
            ->willReturn(false);
        $this
            ->translator
            ->expects($this->once())
            ->method('trans')
            ->with($this->stringContains('quantity_over_max_limit'))
            ->willReturn('maxMessage');
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('maxMessage')
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
            ->method('getMinimumLimit')
            ->with($product)
            ->willReturn(1);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMaximumLimit')
            ->with($product)
            ->willReturn(100);
        $this
            ->validatorService
            ->expects($this->exactly(1))
            ->method('isHigherThanMaxLimit')
            ->with(100, $productRow->productQuantity)
            ->willReturn(false);
        $this
            ->validatorService
            ->expects($this->exactly(1))
            ->method('isLowerThenMinLimit')
            ->with(1, $productRow->productQuantity)
            ->willReturn(true);
        $this
            ->translator
            ->expects($this->once())
            ->method('trans')
            ->with($this->stringContains('quantity_below_min_limit'))
            ->willReturn('minMessage');
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('minMessage')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->willReturn($this->getMock(ConstraintViolationBuilderInterface::class));
        $this->validator->validate($productRow, $this->constraint);
    }

    public function testValidateBothConstraints()
    {
        $product = new ProductStub();
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
            ->method('getMinimumLimit')
            ->with($product)
            ->willReturn(1);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('getMaximumLimit')
            ->with($product)
            ->willReturn(100);
        $this
            ->validatorService
            ->expects($this->exactly(1))
            ->method('isHigherThanMaxLimit')
            ->with(100, $productRow->productQuantity)
            ->willReturn(true);
        $this
            ->validatorService
            ->expects($this->exactly(1))
            ->method('isLowerThenMinLimit')
            ->with(1, $productRow->productQuantity)
            ->willReturn(true);
        $this
            ->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                [$this->stringContains('quantity_over_max_limit')],
                [$this->stringContains('quantity_below_min_limit')]
            )
            ->willReturnOnConsecutiveCalls('maxMessage', 'minMessage');
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->exactly(2))
            ->method('buildViolation')
            ->withConsecutive(['maxMessage'], ['minMessage'])
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->exactly(2))
            ->method('atPath')
            ->willReturn($this->getMock(ConstraintViolationBuilderInterface::class));
        $this->validator->validate($productRow, $this->constraint);
    }
}
