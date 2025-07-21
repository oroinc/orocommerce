<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderEntityNameProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderEntityNameProviderTest extends TestCase
{
    private MockObject&TranslatorInterface $translator;

    private OrderEntityNameProvider $provider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new OrderEntityNameProvider($this->translator);
    }

    public function testGetNameReturnsFalseWhenEntityIsNotOrder(): void
    {
        $result = $this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new \stdClass());
        self::assertFalse($result);
    }

    public function testGetNameWhenShort(): void
    {
        $locale = 'en';
        $order = (new Order())
            ->setIdentifier('123467890');

        $expected = 'Order #' . $order->getIdentifier();
        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                [
                    'oro.order.entity_name.short',
                    ['%order_identifier%' => $order->getIdentifier()],
                    null,
                    $locale,
                    $expected,
                ],
            ]);

        $result = $this->provider->getName(EntityNameProviderInterface::SHORT, $locale, $order);
        self::assertEquals($expected, $result);
    }

    public function testGetNameWhenFull(): void
    {
        $locale = 'en';
        $order = (new Order())
            ->setIdentifier('123467890');

        $expected = 'Order #' . $order->getIdentifier();
        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                [
                    'oro.order.entity_name.full',
                    ['%order_identifier%' => $order->getIdentifier()],
                    null,
                    $locale,
                    $expected,
                ],
            ]);

        $result = $this->provider->getName(EntityNameProviderInterface::FULL, $locale, $order);
        self::assertEquals($expected, $result);
    }

    public function testGetNameWhenNoIdentifier(): void
    {
        $locale = 'en';
        $order = new Order();
        ReflectionUtil::setId($order, 42);

        $expected = 'Order #' . $order->getId();
        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                [
                    'oro.order.entity_name.short',
                    ['%order_identifier%' => (string) $order->getId()],
                    null,
                    $locale,
                    $expected,
                ],
            ]);

        $result = $this->provider->getName(EntityNameProviderInterface::SHORT, $locale, $order);
        self::assertEquals($expected, $result);
    }

    public function testGetNameWhenLocaleIsLocalization(): void
    {
        $order = (new Order())
            ->setIdentifier('123467890');
        $localeCode = 'fr';
        $localization = (new Localization())
            ->setLanguage((new Language())->setCode($localeCode));

        $expected = 'Demande #123467890';
        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                [
                    'oro.order.entity_name.short',
                    ['%order_identifier%' => '123467890'],
                    null,
                    $localeCode,
                ],
            ])
            ->willReturn($expected);

        $result = $this->provider->getName(EntityNameProviderInterface::SHORT, $localization, $order);
        self::assertEquals($expected, $result);
    }

    public function testGetNameDQLReturnsFalseForInvalidClassName(): void
    {
        $result = $this->provider->getNameDQL(EntityNameProviderInterface::SHORT, 'en', \stdClass::class, 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLWhenLocaleIsStringAndShort(): void
    {
        $className = Order::class;
        $alias = 'i';
        $locale = 'en';

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                ['oro.order.entity_name.short', [], null, $locale, 'Order #%order_identifier%'],
            ]);

        $result = $this->provider->getNameDQL(EntityNameProviderInterface::SHORT, $locale, $className, $alias);

        $expectedExpr = sprintf(
            "CONCAT('Order #', %s.identifier, '')",
            $alias
        );

        self::assertStringContainsString($expectedExpr, $result);
    }

    public function testGetNameDQLWhenLocaleIsStringAndFull(): void
    {
        $className = Order::class;
        $alias = 'i';
        $locale = 'en';

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                ['oro.order.entity_name.full', [], null, $locale, 'Order #%order_identifier%'],
            ]);

        $result = $this->provider->getNameDQL(EntityNameProviderInterface::FULL, $locale, $className, $alias);

        $expectedExpr = sprintf(
            "CONCAT('Order #', %s.identifier, '')",
            $alias
        );

        self::assertStringContainsString($expectedExpr, $result);
    }

    public function testGetNameDQLWhenLocaleIsLocalizationAndShort(): void
    {
        $className = Order::class;
        $alias = 'i';
        $localeCode = 'fr';
        $localization = (new Localization())
            ->setLanguage((new Language())->setCode($localeCode));

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                ['oro.order.entity_name.short', [], null, $localeCode, 'Demande #%order_identifier%'],
            ]);

        $result = $this->provider->getNameDQL(EntityNameProviderInterface::SHORT, $localization, $className, $alias);

        $expectedExpr = sprintf(
            "CONCAT('Demande #', %s.identifier, '')",
            $alias
        );

        self::assertStringContainsString($expectedExpr, $result);
    }

    public function testGetNameDQLWhenLocaleIsLocalizationAndFull(): void
    {
        $className = Order::class;
        $alias = 'i';
        $localeCode = 'fr';
        $localization = (new Localization())
            ->setLanguage((new Language())->setCode($localeCode));

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnMap([
                ['oro.order.entity_name.full', [], null, $localeCode, 'Demande #%order_identifier%'],
            ]);

        $result = $this->provider->getNameDQL(EntityNameProviderInterface::FULL, $localization, $className, $alias);

        $expectedExpr = sprintf(
            "CONCAT('Demande #', %s.identifier, '')",
            $alias
        );

        self::assertStringContainsString($expectedExpr, $result);
    }
}
