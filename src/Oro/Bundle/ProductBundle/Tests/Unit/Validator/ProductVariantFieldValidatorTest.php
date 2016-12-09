<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ProductBundle\Provider\CustomVariantFieldsProvider;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantField;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantFieldValidator;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class ProductVariantFieldValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductVariantFieldValidator */
    protected $service;

    /** @var CustomVariantFieldsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $customVariantFieldsProvider;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var array */
    protected $variantFields = [
        'field_first',
        'field_second'
    ];

    /** @var array  */
    protected $incorrectCustomVariantFields = [
        'field_first' => [
            'name' => 'field_first',
            'type' => 'string',
            'label' => 'field_first'
        ],
        'field_third' => [
            'name' => 'field_third',
            'type' => 'string',
            'label' => 'field_third'
        ]
    ];

    /** @var array  */
    protected $correctCustomVariantFields = [
        'field_first' => [
            'name' => 'field_first',
            'type' => 'string',
            'label' => 'field_first'
        ],
        'field_second' => [
            'name' => 'field_second',
            'type' => 'string',
            'label' => 'field_second'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->customVariantFieldsProvider = $this->getMockBuilder(CustomVariantFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new ProductVariantFieldValidator($this->customVariantFieldsProvider);
        $this->service->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->context,
            $this->customVariantFieldsProvider,
            $this->service
        );
    }

    public function testDoesNothingIfEmptyProductCustomFields()
    {
        $product = new Product();
        $productClass = ClassUtils::getClass($product);

        $this->customVariantFieldsProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn([]);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new ProductVariantField());
    }

    public function testAddViolationIfProductDoesNotHaveFields()
    {
        $product = $this->prepareProductWithVariantFields($this->variantFields);
        $productClass = ClassUtils::getClass($product);

        $this->customVariantFieldsProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn([]);

        $this->context->expects($this->exactly(count($this->variantFields)))->method('addViolation');

        $this->service->validate($product, new ProductVariantField());
    }

    public function testDoesNotAddViolationIfVariantFieldsExistInCustomFields()
    {
        $product = $this->prepareProductWithVariantFields($this->variantFields);
        $productClass = ClassUtils::getClass($product);

        $this->customVariantFieldsProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn($this->correctCustomVariantFields);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new ProductVariantField());
    }

    public function testAddViolationIfVariantFieldDoesNotExistInCustomField()
    {
        $product = $this->prepareProductWithVariantFields($this->variantFields);

        $productClass = ClassUtils::getClass($product);

        $this->customVariantFieldsProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($productClass)
            ->willReturn($this->incorrectCustomVariantFields);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new ProductVariantField())->message);

        $this->service->validate($product, new ProductVariantField());
    }

    /**
     * @param array $variantFields
     * @return Product
     */
    private function prepareProductWithVariantFields(array $variantFields)
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields($variantFields);

        return $product;
    }
}
