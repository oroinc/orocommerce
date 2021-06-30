<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySku;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProductBySkuValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductBySku
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AclHelper
     */
    private $aclHelper;

    /**
     * @var ProductBySkuValidator
     */
    protected $validator;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = $this->createMock(ProductBySku::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->validator = new ProductBySkuValidator($this->registry, $this->aclHelper);
        $this->validator->initialize($this->context);
    }

    public function testValidateNoValue()
    {
        $this->registry->expects($this->never())
            ->method('getRepository');

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate('', $this->constraint);
    }

    /**
     * @param bool $useOptions
     * @param string $sku
     * @param Product|null $product
     * @dataProvider validateProvider
     */
    public function testValidate($useOptions, $sku, $product)
    {
        if ($useOptions) {
            $products = [];
            if ($product) {
                $products[mb_strtoupper($sku)] = $product;
            }
        } else {
            $products = null;
        }

        $config = $this->createMock('Symfony\Component\Form\FormConfigInterface');
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

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('offsetExists')
            ->with('products')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('offsetGet')
            ->with('products')
            ->willReturn($form);
        $form->expects($this->any())->method('getConfig')->willReturn($config);

        $this->context->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('[products]');
        $this->context->expects($this->once())
            ->method('getRoot')
            ->willReturn($form);

        if (!$useOptions) {
            $repository = $this->createMock(ProductRepository::class);

            $query = $this->createMock(AbstractQuery::class);
            $query->expects($this->once())
                ->method('getOneOrNullResult')
                ->willReturn($product);
            $queryBuilder = $this->createMock(QueryBuilder::class);
            $repository->expects($this->once())
                ->method('getBySkuQueryBuilder')
                ->with($sku)
                ->willReturn($queryBuilder);
            $this->aclHelper
                ->expects($this->once())
                ->method('apply')
                ->with($queryBuilder)
                ->willReturn($query);

            $this->registry->expects($this->once())
                ->method('getRepository')
                ->with(Product::class)
                ->will($this->returnValue($repository));
        }

        $this->context->expects($product ? $this->never() : $this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $this->validator->validate($sku, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'fail repo' => [false, 'sku1', null],
            'success repo' => [false, 'sku1абв', new Product()],
            'fail options' => [true, 'Sku2', null],
            'success options' => [true, 'Sku2Абв', new Product()],
        ];
    }
}
