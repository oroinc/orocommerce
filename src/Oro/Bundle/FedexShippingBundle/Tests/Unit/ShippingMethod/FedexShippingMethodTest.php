<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use PHPUnit\Framework\TestCase;

class FedexShippingMethodTest extends TestCase
{
    const IDENTIFIER = 'id';
    const LABEL = 'label';
    const ICON_PATH = 'path';
    const ENABLED = true;

    public function testGetters()
    {
        $settings = new FedexIntegrationSettings();
        $types = [
            $this->createMethodType('test1'),
            $this->createMethodType('test2'),
        ];
        $method = new FedexShippingMethod(
            self::IDENTIFIER,
            self::LABEL,
            self::ICON_PATH,
            self::ENABLED,
            $settings,
            $types
        );

        static::assertTrue($method->isGrouped());
        static::assertSame(self::ENABLED, $method->isEnabled());
        static::assertSame(self::IDENTIFIER, $method->getIdentifier());
        static::assertSame(self::LABEL, $method->getLabel());
        static::assertSame(self::ICON_PATH, $method->getIcon());
        static::assertSame($types, $method->getTypes());
        static::assertSame(FedexShippingMethodOptionsType::class, $method->getOptionsConfigurationFormType());
        static::assertSame(20, $method->getSortOrder());

        static::assertNull($method->getType('no'));
        static::assertSame($types[0], $method->getType('test1'));
    }

    public function testGetTrackingLinkMatches()
    {
        $settings = new FedexIntegrationSettings();
        $types = [];
        $method = new FedexShippingMethod(
            self::IDENTIFIER,
            self::LABEL,
            self::ICON_PATH,
            self::ENABLED,
            $settings,
            $types
        );
        $matchingNumbers = [
            '9612345676543456787654',
            '145678765432123',
            '345676543212',
            '98123454321 1111 2222',
            '9812354321 11112222',
            '98111111 2222',
            '9812345432111112222',
            '9822 11112222 333',
            '9822 11112222333',
            '475948574839472',
        ];

        foreach ($matchingNumbers as $number) {
            static::assertEquals(
                FedexShippingMethod::TRACKING_URL . $number,
                $method->getTrackingLink($number)
            );
        }

        static::assertNull($method->getTrackingLink('000'));
    }

    /**
     * @param string $identifier
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ShippingMethodTypeInterface
     */
    private function createMethodType(string $identifier)
    {
        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type
            ->expects(static::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $type;
    }
}
