<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySku;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductBySkuValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        parent::setUp();
    }

    protected function createValidator(): ProductBySkuValidator
    {
        return new ProductBySkuValidator($this->doctrine, $this->aclHelper);
    }

    public function testValidateNoValue()
    {
        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $constraint = new ProductBySku();
        $this->validator->validate('', $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(bool $useOptions, string $sku, ?Product $product)
    {
        if ($useOptions) {
            $products = [];
            if (null !== $product) {
                $products[mb_strtoupper($sku)] = $product;
            }
        } else {
            $products = null;
        }

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    'product' => null,
                    'product_field' => 'product',
                    'product_holder' => null,
                ]
            );
        $config->expects($this->once())
            ->method('hasOption')
            ->with('products')
            ->willReturn(true);
        $config->expects($this->once())
            ->method('getOption')
            ->with('products')
            ->willReturn($products);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->exactly(2))
            ->method('offsetExists')
            ->with('products')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('products')
            ->willReturn($form);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        if (!$useOptions) {
            $query = $this->createMock(AbstractQuery::class);
            $query->expects($this->once())
                ->method('getOneOrNullResult')
                ->willReturn($product);

            $queryBuilder = $this->createMock(QueryBuilder::class);

            $repository = $this->createMock(ProductRepository::class);
            $repository->expects($this->once())
                ->method('getBySkuQueryBuilder')
                ->with($sku)
                ->willReturn($queryBuilder);

            $this->aclHelper->expects($this->once())
                ->method('apply')
                ->with($queryBuilder)
                ->willReturn($query);

            $this->doctrine->expects($this->once())
                ->method('getRepository')
                ->with(Product::class)
                ->willReturn($repository);
        }

        $this->setRoot($form);
        $this->setPropertyPath('[products]');
        $constraint = new ProductBySku();
        $this->validator->validate($sku, $constraint);

        if (null === $product) {
            $this->buildViolation($constraint->message)
                ->atPath('[products]')
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validateProvider(): array
    {
        return [
            'fail repo' => [false, 'sku1', null],
            'success repo' => [false, 'sku1абв', new Product()],
            'fail options' => [true, 'Sku2', null],
            'success options' => [true, 'Sku2Абв', new Product()],
        ];
    }
}
