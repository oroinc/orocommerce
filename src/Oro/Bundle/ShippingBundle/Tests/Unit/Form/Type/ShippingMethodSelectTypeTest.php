<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodSelectType;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingMethodSelectTypeTest extends FormIntegrationTestCase
{
    private const ICON = 'bundles/icon-uri.png';
    private const ICON_URL = '/assets/bundles/icon-uri.png';
    private const METHOD_1 = 'shipping_method_1';
    private const METHOD_2 = 'shipping_method_2';

    private const METHODS = [
        self::METHOD_1 => 'Method 1',
        self::METHOD_2 => 'Method 2',
    ];

    private const ICONS = [
        [self::METHOD_1, self::ICON],
        [self::METHOD_2, '']
    ];

    /** @var ShippingMethodIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $iconProvider;

    /** @var ShippingMethodChoicesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $choicesProvider;

    /** @var AssetHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $assetHelper;

    /** @var ShippingMethodSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->choicesProvider = $this->createMock(ShippingMethodChoicesProvider::class);
        $this->iconProvider = $this->createMock(ShippingMethodIconProviderInterface::class);
        $this->assetHelper = $this->createMock(AssetHelper::class);

        $this->type = new ShippingMethodSelectType($this->choicesProvider, $this->iconProvider, $this->assetHelper);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $this->choicesProvider->expects(self::once())
            ->method('getMethods')
            ->willReturn(self::METHODS);

        $this->iconProvider->expects(self::exactly(2))
            ->method('getIcon')
            ->willReturnMap(self::ICONS);

        $this->assetHelper->expects(self::once())
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

        self::assertEquals($expected, $resolved, 'Default options are set');
        self::assertSame(
            ['data-icon' => self::ICON_URL],
            $resolved['choice_attr'](self::METHOD_1),
            'Attribute data-icon is set when icon URI is not empty'
        );
        self::assertSame(
            [],
            $resolved['choice_attr'](self::METHOD_2),
            'Attribute data-icon is NOT set when icon URI is empty'
        );
    }

    public function testGetParent()
    {
        self::assertSame(OroChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        self::assertSame('oro_shipping_method_select', $this->type->getBlockPrefix());
    }
}
