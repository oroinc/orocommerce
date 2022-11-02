<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductRowQuantity;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductRowQuantityValidator;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductRowQuantityValidatorTest extends ConstraintValidatorTestCase
{
    /** @var QuantityToOrderValidatorService|\PHPUnit\Framework\MockObject\MockObject */
    private $validatorService;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->validatorService = $this->createMock(QuantityToOrderValidatorService::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new ProductRowQuantityValidator(
            $this->validatorService,
            $this->doctrineHelper,
            $this->aclHelper
        );
    }

    public function testValidateEmptyValue()
    {
        $constraint = new ProductRowQuantity();
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoProductValue()
    {
        $constraint = new ProductRowQuantity();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoProduct()
    {
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(ProductRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with($productRow->productSku)
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $constraint = new ProductRowQuantity();
        $this->validator->validate($productRow, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateQuantityNotNumeric()
    {
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 'string';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(new Product());
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(ProductRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with($productRow->productSku)
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $constraint = new ProductRowQuantity();
        $this->validator->validate($productRow, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoConstraint()
    {
        $product = new Product();
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 10;
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(ProductRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with($productRow->productSku)
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);
        $this->validatorService->expects($this->once())
            ->method('getMinimumErrorIfInvalid')
            ->with($product)
            ->willReturn(false);
        $this->validatorService->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->with($product)
            ->willReturn(false);

        $constraint = new ProductRowQuantity();
        $this->validator->validate($productRow, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateMaxLimitConstraint()
    {
        $product = new ProductStub();
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 10;
        $maxErrorMessage = 'maxMessage';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(ProductRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with($productRow->productSku)
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);
        $this->validatorService->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->with($product)
            ->willReturn($maxErrorMessage);
        // should not be called as maximum validation is triggered
        $this->validatorService->expects($this->never())
            ->method('getMinimumErrorIfInvalid')
            ->with($product);

        $constraint = new ProductRowQuantity();
        $this->validator->validate($productRow, $constraint);

        $this->buildViolation($maxErrorMessage)
            ->atPath('property.path.productQuantity')
            ->assertRaised();
    }

    public function testValidateMinLimitConstraint()
    {
        $product = new ProductStub();
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 10;
        $minMessage = 'minMessage';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMock(ProductRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with($productRow->productSku)
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);
        $this->validatorService->expects($this->once())
            ->method('getMinimumErrorIfInvalid')
            ->with($product)
            ->willReturn($minMessage);
        $this->validatorService->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->with($product)
            ->willReturn(false);

        $constraint = new ProductRowQuantity();
        $this->validator->validate($productRow, $constraint);

        $this->buildViolation($minMessage)
            ->atPath('property.path.productQuantity')
            ->assertRaised();
    }
}
