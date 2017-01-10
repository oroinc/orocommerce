<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendVariantFiledType;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\EnumSelectTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class FrontendVariantFiledTypeTest extends FormIntegrationTestCase
{
    const FIELD_COLOR = 'testColor';
    const FIELD_NEW = 'testNew';
    const PRODUCT_CLASS = Product::class;

    /** @var FrontendVariantFiledType */
    protected $type;

    /** @var CustomFieldProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $customFieldProvider;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $productVariantAvailabilityProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var CustomFieldProvider|\PHPUnit_Framework_MockObject_MockObject $customFieldProvider */
        $this->customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productVariantAvailabilityProvider = $this->getMockBuilder(ProductVariantAvailabilityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new FrontendVariantFiledType(
            $this->customFieldProvider,
            $this->productVariantAvailabilityProvider,
            self::PRODUCT_CLASS
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->productVariantAvailabilityProvider, $this->customFieldProvider, $this->type);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_product_frontend_variant_field', $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_product_frontend_variant_field', $this->type->getBlockPrefix());
    }

    public function testBuildFormSimpleProduct()
    {
        $product = new Product();
        $product
            ->setType(Product::TYPE_SIMPLE)
            ->setVariantFields([self::FIELD_COLOR, self::FIELD_NEW]);

        $options = [
            'product' => $product,
        ];

        $form = $this->factory->create($this->type, [], $options);

        $this->assertCount(1, $form->getConfig()->getEventDispatcher()->getListeners(FormEvents::PRE_SET_DATA));
        $this->assertCount(2, $form->getConfig()->getEventDispatcher()->getListeners(FormEvents::PRE_SUBMIT));
        $this->assertFalse($form->has(self::FIELD_COLOR));
        $this->assertFalse($form->has(self::FIELD_NEW));
    }

    public function testBuildFormConfigurableProduct()
    {
        $product = new Product();
        $product
            ->setType(Product::TYPE_CONFIGURABLE)
            ->setVariantFields([self::FIELD_COLOR, self::FIELD_NEW]);

        $options = [
            'product' => $product,
        ];

        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with(Product::class)
            ->willReturn([
                self::FIELD_COLOR => [
                    'type' => 'enum',
                    'label' => 'TestColorLabel'
                ],
                self::FIELD_NEW => [
                    'type' => 'boolean',
                    'label' => 'TestNewLabel'
                ]
            ]);

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getVariantFieldsWithAvailability')
            ->with($product, [])
            ->willReturn([
                self::FIELD_COLOR => [
                    'red' => false,
                    'green' => true
                ],
                self::FIELD_NEW => [true, false]
            ]);

        $form = $this->factory->create($this->type, [], $options);

        $this->assertTrue($form->has(self::FIELD_COLOR));
        $this->assertTrue($form->has(self::FIELD_NEW));

        $colorConfig = $form->get(self::FIELD_COLOR)->getConfig();
        $newConfig = $form->get(self::FIELD_NEW)->getConfig();

        $this->assertTrue($colorConfig->getOption('required'));
        $this->assertFalse($colorConfig->getOption('placeholder'));
        $this->assertEquals('oro_enum_select', $colorConfig->getType()->getName());
        $this->assertEquals(
            ExtendHelper::generateEnumCode(Product::class, self::FIELD_COLOR),
            $colorConfig->getOption('enum_code')
        );
        $this->assertEquals('TestColorLabel', $colorConfig->getOption('label'));
        $this->assertEquals(['red'], $colorConfig->getOption('disabled_values'));

        $this->assertEquals('choice', $newConfig->getType()->getName());
        $this->assertEquals([0, 1], $newConfig->getOption('choices'));
        $this->assertEquals('TestNewLabel', $newConfig->getOption('label'));
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['product']);

        $this->type->configureOptions($resolver);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "product" is missing.
     */
    public function testBuildFormWithoutProductInOptions()
    {
        $this->factory->create($this->type);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Incorrect type. Expected "boolean" or "enum", but "string" given
     */
    public function testBuildWithNonExpectedFiledType()
    {
        $product = new Product();
        $product
            ->setType(Product::TYPE_CONFIGURABLE)
            ->setVariantFields(['stringField']);

        $options['product'] = $product;

        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with(Product::class)
            ->willReturn([
                'stringField' => [
                    'type' => 'string',
                    'label' => 'TestColorLabel'
                ]
            ]);
        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getVariantFieldsWithAvailability')
            ->with($product, [])
            ->willReturn(['stringField' => []]);

        $this->factory->create($this->type, [], $options);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "product" with value stdClass is expected to be of type "Oro\Bundle\ProductBundle\Entity\Product", but is of type "stdClass".
     */
    // @codingStandardsIgnoreEnd
    public function testBuildWhenRequiredFieldProductHasOtherObject()
    {
        $options['product'] = new \stdClass();
        $this->factory->create($this->type, [], $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $enumSelectStub = new EnumSelectTypeStub();
        $choiceType = new ChoiceType();

        return [
            new PreloadedExtension(
                [
                    EnumSelectType::NAME => $enumSelectStub,
                    $choiceType->getName() => $choiceType,
                ],
                []
            )
        ];
    }
}
