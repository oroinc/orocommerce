<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /** @var CustomFieldProvider|\PHPUnit_Framework_MockObject_MockObject $customFieldProvider */
    protected $customFieldProvider;

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

        $this->type = new FrontendVariantFiledType($this->customFieldProvider, self::PRODUCT_CLASS);
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

        $form = $this->factory->create($this->type, null, $options);

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

        $form = $this->factory->create($this->type, null, $options);

        $this->assertTrue($form->has(self::FIELD_COLOR));
        $this->assertTrue($form->has(self::FIELD_NEW));

        $colorField = $form->get(self::FIELD_COLOR);
        $newField = $form->get(self::FIELD_NEW);

        $colorConfig = $colorField->getConfig();
        $newConfig = $newField->getConfig();

        $this->assertTrue($colorConfig->getOption('required'));
        $this->assertFalse($colorConfig->getOption('placeholder'));
        $this->assertEquals('oro_enum_select', $colorConfig->getType()->getName());
        $this->assertEquals(
            ExtendHelper::generateEnumCode(Product::class, self::FIELD_COLOR),
            $colorConfig->getOption('enum_code')
        );
        $this->assertEquals('TestColorLabel', $colorConfig->getOption('label'));

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

        $this->factory->create($this->type, null, $options);
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
