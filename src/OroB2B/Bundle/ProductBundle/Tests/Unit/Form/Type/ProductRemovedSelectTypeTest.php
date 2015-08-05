<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductHolderType;

class ProductRemovedSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductRemovedSelectType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->will(static::returnCallback(function ($id, array $params) {
                return $id . ':' .$params['{title}'];
            }))
        ;

        $this->formType = new ProductRemovedSelectType($this->translator);
    }

    public function testGetName()
    {
        static::assertEquals(ProductRemovedSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        static::assertEquals(ProductSelectType::NAME, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with(static::isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    static::assertArrayHasKey('autocomplete_alias', $options);
                    static::assertArrayHasKey('create_form_route', $options);
                    static::assertArrayHasKey('configs', $options);
                    static::assertEquals('orob2b_product', $options['autocomplete_alias']);
                    static::assertEquals('orob2b_product_create', $options['create_form_route']);
                    static::assertEquals(
                        ['placeholder' => 'orob2b.product.form.choose'],
                        $options['configs']
                    );
                }
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @param ProductHolderInterface $productHolder
     * @param array $expectedData
     *
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData(ProductHolderInterface $productHolder = null, array $expectedData = [])
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();

        $formParent = $this->factory->create(new StubProductHolderType(), $productHolder);

        $form = $this->factory->create($this->formType);
        $form->setParent($formParent);
        $this->formType->preSetData(new FormEvent($form, $productHolder));
        $options = $formParent->get('product')->getConfig()->getOptions();

        foreach ($expectedData as $field => $value) {
            static::assertEquals($value, $options[$field]);
        }
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        return [
            'empty item' => [
                'productHolder' => null,
                'expectedData'  => [
                    'configs'   => [
                        'placeholder'   => 'orob2b.product.form.choose',
                    ],
                    'required'          => true,
                    'create_enabled'    => true,
                    'label'             => 'orob2b.product.entity_label',
                ],
            ],

            'filled item' => [
                'productHolder' => $this->createProductHolder(1, 'sku', new Product()),
                'expectedData'  => [
                    'configs'   => [
                        'placeholder'   => 'orob2b.product.form.choose',
                    ],
                    'required'          => true,
                    'create_enabled'    => true,
                    'label'             => 'orob2b.product.entity_label',
                ],
            ],

            'deleted product' => [
                'productHolder' => $this->createProductHolder(1, 'sku', null),
                'expectedData'  => [
                    'configs'   => [
                        'placeholder' => 'orob2b.product.removed:sku',
                    ],
                    'required'  => true,
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param string $productSku
     * @param Product $product
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
     */
    protected function createProductHolder($id, $productSku, Product $product = null)
    {
        /* @var $productHolder \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface */
        $productHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolder
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id)
        ;
        $productHolder
            ->expects(static::any())
            ->method('getProduct')
            ->willReturn($product)
        ;
        $productHolder
            ->expects(static::any())
            ->method('getProductSku')
            ->willReturn($productSku)
        ;

        return $productHolder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productSelectType = new ProductSelectTypeStub();
        $productRemovedSelectType = new ProductRemovedSelectType($this->translator);
        $entityType = new EntityType(['1']);

        return [
            new PreloadedExtension(
                [
                    $productSelectType->getName() => $productSelectType,
                    $productRemovedSelectType->getName() => $productRemovedSelectType,
                    $entityType->getName() => $entityType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
