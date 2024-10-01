<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\QuoteEntityNameProvider;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private QuoteEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->with('oro.frontend.sale.quote.title.label')
            ->willReturnCallback(function (string $id, array $parameters) {
                return str_replace('%id%', $parameters['%id%'] ?? '%id%', 'Quote - %id%');
            });

        $this->provider = new QuoteEntityNameProvider($translator);
    }

    /**
     * @dataProvider getNameDataProvider
     */
    public function testGetName(string $format, ?string $locale, object $entity, string|false $expected): void
    {
        $this->assertEquals($expected, $this->provider->getName($format, $locale, $entity));
    }

    public function getNameDataProvider(): array
    {
        $quote = new Quote();
        ReflectionUtil::setId($quote, 123);

        return [
            'unsupported class' => [
                'format' => '',
                'locale' => null,
                'entity' => new \stdClass(),
                'expected' => false
            ],
            'unsupported format' => [
                'format' => '',
                'locale' => null,
                'entity' => $quote,
                'expected' => false
            ],
            'correct data' => [
                'format' => EntityNameProviderInterface::FULL,
                'locale' => 'en',
                'entity' => $quote,
                'expected' => 'Quote - 123'
            ]
        ];
    }

    /**
     * @dataProvider getNameDQLDataProvider
     */
    public function testGetNameDQL(
        string $format,
        ?string $locale,
        string $className,
        string $alias,
        string|false $expected
    ): void {
        $this->assertEquals($expected, $this->provider->getNameDQL($format, $locale, $className, $alias));
    }

    public function getNameDQLDataProvider(): array
    {
        return [
            'unsupported class Name' => [
                'format' => '',
                'locale' => null,
                'className' => '',
                'alias' => 'test',
                'expected' => false
            ],
            'unsupported format' => [
                'format' => '',
                'locale' => null,
                'className' => Quote::class,
                'alias' => 'test',
                'expected' => false
            ],
            'correct data' => [
                'format' => EntityNameProviderInterface::FULL,
                'locale' => 'en',
                'className' => Quote::class,
                'alias' => 'test',
                'expected' => 'CONCAT(\'Quote - \', test.id, \'\')'
            ]
        ];
    }
}
