<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingMethodChoicesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ShippingMethodChoicesProvider */
    private $choicesProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->choicesProvider = new ShippingMethodChoicesProvider(
            $this->shippingMethodProvider,
            $this->translator
        );
    }

    private function getShippingMethod(
        string $identifier,
        int $sortOrder,
        string $label,
        bool $enabled
    ): ShippingMethodStub {
        $shippingMethod = new ShippingMethodStub();
        $shippingMethod->setIdentifier($identifier);
        $shippingMethod->setSortOrder($sortOrder);
        $shippingMethod->setLabel($label);
        $shippingMethod->setIsEnabled($enabled);

        return $shippingMethod;
    }

    /**
     * @dataProvider methodsProvider
     */
    public function testGetMethods(array $methods, array $result, bool $translate = false)
    {
        $translation = [
            ['flat rate', [], null, null, 'flat rate translated'],
            ['ups', [], null, null, 'ups translated'],
        ];

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn($methods);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnMap($translation);

        $this->assertEquals($result, $this->choicesProvider->getMethods($translate));
    }

    public function methodsProvider(): array
    {
        return
            [
                'some_methods' => [
                    'methods' => [
                        'flat_rate' => $this->getShippingMethod('flat_rate', 1, 'flat rate', true),
                        'disabled' => $this->getShippingMethod('disabled', 2, 'disabled', false),
                        'ups' => $this->getShippingMethod('ups', 3, 'ups', true)
                    ],
                    'result' => ['flat rate' => 'flat_rate', 'ups' => 'ups'],
                    'translate' => false,
                ],
                'some_methods_with_translation' => [
                    'methods' => [
                        'flat_rate' => $this->getShippingMethod('flat_rate', 1, 'flat rate', true),
                        'disabled' => $this->getShippingMethod('disabled', 2, 'disabled', false),
                        'ups' => $this->getShippingMethod('ups', 3, 'ups', true)
                    ],
                    'result' => ['flat rate translated' => 'flat_rate', 'ups translated' => 'ups'],
                    'translate' => true,
                ],
                'no_methods' => [
                    'methods' => [],
                    'result' => [],
                ],
            ];
    }
}
