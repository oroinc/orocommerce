<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodSelectType;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingMethodSelectTypeTest extends FormIntegrationTestCase
{
    const ICON = 'bundles/icon-uri.png';
    const ICON_URL = '/assets/bundles/icon-uri.png';
    const METHOD_1 = 'shipping_method_1';
    const METHOD_2 = 'shipping_method_2';

    const METHODS = [
        self::METHOD_1 => 'Method 1',
        self::METHOD_2 => 'Method 2',
    ];

    const ICONS = [
        [self::METHOD_1, self::ICON],
        [self::METHOD_2, '']
    ];

    /**
     * @var ShippingMethodIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $iconProvider;

    /**
     * @var ShippingMethodChoicesProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $choicesProvider;

    /**
     * @var AssetHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $assetHelper;

    /**
     * @var ShippingMethodSelectType
     */
    private $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->choicesProvider = $this->createMock(ShippingMethodChoicesProviderInterface::class);
        $this->iconProvider = $this->createMock(ShippingMethodIconProviderInterface::class);
        $this->assetHelper = $this->createMock(AssetHelper::class);

        $this->type = new ShippingMethodSelectType($this->choicesProvider, $this->iconProvider, $this->assetHelper);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $this->choicesProvider
            ->method('getMethods')
            ->willReturn(self::METHODS);

        $this->iconProvider
            ->method('getIcon')
            ->willReturnMap(self::ICONS);

        $this->assetHelper
            ->method('getUrl')
            ->with(self::ICON)
            ->willReturn(self::ICON_URL);

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $resolved = $resolver->resolve();

        $expected = [
            'placeholder' => 'oro.shipping.sections.shippingrule_configurations.placeholder.label',
            'choices' => self::METHODS,
            'choice_attr' => function () {
            },
            'configs' => [
                'showIcon' => true,
                'minimumResultsForSearch' => 1,
            ]
        ];

        static::assertEquals($expected, $resolved, 'Default options are set');
        static::assertSame(
            ['data-icon' => self::ICON_URL],
            $resolved['choice_attr'](self::METHOD_1),
            'Attribute data-icon is set when icon URI is not empty'
        );
        static::assertSame(
            [],
            $resolved['choice_attr'](self::METHOD_2),
            'Attribute data-icon is NOT set when icon URI is empty'
        );
    }

    public function testGetParent()
    {
        $this->assertSame(OroChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertSame(ShippingMethodSelectType::NAME, $this->type->getBlockPrefix());
    }
}
