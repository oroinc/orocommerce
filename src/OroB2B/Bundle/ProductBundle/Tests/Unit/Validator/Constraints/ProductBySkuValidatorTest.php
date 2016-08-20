<?php

namespace Oro\Bundle\ProductBundle\Tests\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySku;

class ProductBySkuValidatorTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroProductBundle:Product';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductBySku
     */
    protected $constraint;

    /**
     * @var ProductBySkuValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint = $this->getMockBuilder('Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySku')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new ProductBySkuValidator($this->registry);
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
                $products[strtoupper($sku)] = $product;
            }
        } else {
            $products = null;
        }

        $config = $this->getMock('Symfony\Component\Form\FormConfigInterface');
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

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
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
            /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
            $repository = $this
                ->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $repository->expects($this->once())
                ->method('findOneBySku')
                ->with($sku)
                ->will($this->returnValue($product));

            $this->registry->expects($this->once())
                ->method('getRepository')
                ->with(self::PRODUCT_CLASS)
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
            'fail repo' => [false, 'S12', null],
            'success repo' => [false, 'S12_1099', new Product()],
            'fail options' => [true, 'S12', null],
            'success options' => [true, 'S12_1099', new Product()],
        ];
    }
}
