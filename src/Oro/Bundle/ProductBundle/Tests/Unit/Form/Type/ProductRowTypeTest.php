<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class ProductRowTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConstraintValidator
     */
    protected $validator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductUnitsProvider
     */
    protected $productUnitsProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->validator = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->productUnitsProvider
            ->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([]);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->validator);
    }

    /**
     * @dataProvider submitDataProvider
     * @param array|null $defaultData
     * @param array $submittedData
     * @param ProductRow $expectedData
     * @param array $options
     */
    public function testSubmit($defaultData, array $submittedData, ProductRow $expectedData, array $options = [])
    {
        if (count($options)) {
            $this->validator->expects($this->once())
                ->method('validate')
                ->willReturn(true);
        }

        $form = $this->factory->create(ProductRowType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $unitsProviderMock = $this->createMock(ProductUnitsProvider::class);
        $unitsProviderMock->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([]);

        return [
            new PreloadedExtension(
                [
                    ProductAutocompleteType::class => new StubProductAutocompleteType(),
                    ProductUnitsType::class => new ProductUnitsType($unitsProviderMock),
                    ProductRowType::class => new ProductRowType($this->productUnitsProvider)
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->createMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');
        $factory->expects($this->any())
            ->method('getInstance')
            ->willReturnCallback(
                function (Constraint $constraint) {
                    $className = $constraint->validatedBy();

                    if ($className === 'oro_product_product_by_sku_validator') {
                        $this->validators[$className] = $this->validator;
                    }

                    if (!isset($this->validators[$className]) ||
                        $className === 'Symfony\Component\Validator\Constraints\CollectionValidator'
                    ) {
                        $this->validators[$className] = new $className();
                    }

                    return $this->validators[$className];
                }
            );

        return $factory;
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_001',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '10'
                ],
                'expectedData' => $this->createProductRow('SKU_001', '10')
            ],
            'with default data' => [
                'defaultData' => $this->createProductRow('SKU_001', '10'),
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '20'
                ],
                'expectedData' =>$this->createProductRow('SKU_002', '20')
            ],
            'with default data and validation' => [
                'defaultData' => $this->createProductRow('SKU_001', '10'),
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '20'
                ],
                'expectedData' => $this->createProductRow('SKU_002', '20'),
                'options' => [
                    'validation_required' => true
                ]
            ]
        ];
    }

    public function testBuildView()
    {
        $product = new Product();
        $product->setSku('sku123Абв');

        $view = new FormView();

        /** @var FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $config = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    'product' => $product,
                    'product_field' => 'product',
                    'product_holder' => null,
                ]
            );

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('getConfig')->willReturn($config);

        $formType = new ProductRowType($this->productUnitsProvider);
        $formType->buildView($view, $form, []);

        $this->assertEquals($product, $view->vars['product']);
    }

    public function testGetProductFromParent()
    {
        $product = new Product();
        $product->setSku('sku1Абв');

        $view = new FormView();

        /** @var FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject $form */
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
            ->method('getOption')
            ->with('products')
            ->willReturn(
                [
                    'SKU1АБВ' => $product,
                ]
            );

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $parentForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $parentForm->expects($this->any())->method('getConfig')->willReturn($config);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $skuField = $this->createMock('Symfony\Component\Form\FormInterface');
        $skuField->expects($this->once())->method('getData')->willReturn('sku1Абв');

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('getConfig')->willReturn($config);
        $form->expects($this->any())->method('getParent')->willReturn($parentForm);
        $form->expects($this->once())
            ->method('get')
            ->with(ProductDataStorage::PRODUCT_SKU_KEY)
            ->willReturn($skuField);

        $formType = new ProductRowType($this->productUnitsProvider);
        $formType->buildView($view, $form, []);

        $this->assertEquals($product, $view->vars['product']);
    }

    /**
     * @param string $sku
     * @param string $qty
     * @return ProductRow
     */
    protected function createProductRow($sku, $qty)
    {
        $productRow = new ProductRow();
        $productRow->productSku = $sku;
        $productRow->productQuantity= $qty;

        return $productRow;
    }
}
