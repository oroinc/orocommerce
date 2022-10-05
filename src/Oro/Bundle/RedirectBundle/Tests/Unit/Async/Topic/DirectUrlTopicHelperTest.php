<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Async\Topic\DirectUrlTopicHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DirectUrlTopicHelperTest extends \PHPUnit\Framework\TestCase
{
    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private DirectUrlTopicHelper $helper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->helper = new DirectUrlTopicHelper($this->configManager);
    }

    /**
     * @dataProvider configureIdOptionWhenValidDataProvider
     */
    public function testConfigureIdOptionWhenValid(array $body): void
    {
        $resolver = new OptionsResolver();
        $this->helper->configureIdOption($resolver);

        self::assertEquals($body, $resolver->resolve($body));
    }

    public function configureIdOptionWhenValidDataProvider(): array
    {
        return [
            ['body' => [DirectUrlMessageFactory::ID => 42]],
            ['body' => [DirectUrlMessageFactory::ID => [42, 4242]]],
        ];
    }

    /**
     * @dataProvider configureIdOptionWhenInvalidDataProvider
     */
    public function testConfigureIdOptionWhenInvalid(array $body, string $expectedErrorMessage): void
    {
        $resolver = new OptionsResolver();
        $this->helper->configureIdOption($resolver);

        $this->expectErrorMessageMatches($expectedErrorMessage);
        $resolver->resolve($body);
    }

    public function configureIdOptionWhenInvalidDataProvider(): array
    {
        return [
            ['body' => [], 'expectedErrorMessage' => '/The required option "id" is missing./'],
            [
                'body' => [DirectUrlMessageFactory::ID => new \stdClass()],
                'expectedErrorMessage' => '/The option "id" with value stdClass is expected '
                    . 'to be of type "int" or "array"/',
            ],
        ];
    }

    /**
     * @dataProvider configureEntityClassOptionWhenValidDataProvider
     */
    public function testConfigureEntityClassOptionWhenValid(array $body): void
    {
        $resolver = new OptionsResolver();
        $this->helper->configureEntityClassOption($resolver);

        self::assertEquals($body, $resolver->resolve($body));
    }

    public function configureEntityClassOptionWhenValidDataProvider(): array
    {
        return [
            [
                'body' => [
                    DirectUrlMessageFactory::ENTITY_CLASS_NAME => get_class(
                        $this->createMock(SluggableInterface::class)
                    ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider configureEntityClassOptionWhenInvalidDataProvider
     */
    public function testConfigureEntityClassOptionWhenInvalid(array $body, string $expectedErrorMessage): void
    {
        $resolver = new OptionsResolver();
        $this->helper->configureEntityClassOption($resolver);

        $this->expectErrorMessageMatches($expectedErrorMessage);
        $resolver->resolve($body);
    }

    public function configureEntityClassOptionWhenInvalidDataProvider(): array
    {
        return [
            ['body' => [], 'expectedErrorMessage' => '/The required option "class" is missing./'],
            [
                'body' => [DirectUrlMessageFactory::ENTITY_CLASS_NAME => new \stdClass()],
                'expectedErrorMessage' => '/The option "class" with value stdClass is expected to be of type "string"/',
            ],
            [
                'body' => [DirectUrlMessageFactory::ENTITY_CLASS_NAME => 'Acme'],
                'expectedErrorMessage' => '/The option "class" was expected to contain FQCN of the class implementing/',
            ],
            [
                'body' => [DirectUrlMessageFactory::ENTITY_CLASS_NAME => \stdClass::class],
                'expectedErrorMessage' => '/The option "class" was expected to contain FQCN of the class implementing/',
            ],
        ];
    }

    /**
     * @dataProvider configureRedirectOptionWhenValidDataProvider
     */
    public function testConfigureRedirectOptionWhenValid(array $body, array $expected): void
    {
        $resolver = new OptionsResolver();
        $this->helper->configureRedirectOption($resolver);

        self::assertEquals($expected, $resolver->resolve($body));
    }

    public function configureRedirectOptionWhenValidDataProvider(): array
    {
        return [
            ['body' => [], 'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => true]],
            [
                'body' => [DirectUrlMessageFactory::CREATE_REDIRECT => false],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => false],
            ],
            [
                'body' => [DirectUrlMessageFactory::CREATE_REDIRECT => true],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => true],
            ],
        ];
    }

    /**
     * @dataProvider configureRedirectOptionWhenValidAndSystemConfigSetDataProvider
     */
    public function testConfigureRedirectOptionWhenValidAndSystemConfigSet(
        string $strategy,
        array $body,
        array $expected
    ): void {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        $resolver = new OptionsResolver();
        $this->helper->configureRedirectOption($resolver);

        self::assertEquals($expected, $resolver->resolve($body));
    }

    public function configureRedirectOptionWhenValidAndSystemConfigSetDataProvider(): array
    {
        return [
            [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'body' => [],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => true],
            ],
            [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'body' => [DirectUrlMessageFactory::CREATE_REDIRECT => false],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => true],
            ],
            [
                'strategy' => Configuration::STRATEGY_ALWAYS,
                'body' => [DirectUrlMessageFactory::CREATE_REDIRECT => true],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => true],
            ],
            [
                'strategy' => Configuration::STRATEGY_NEVER,
                'body' => [],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => false],
            ],
            [
                'strategy' => Configuration::STRATEGY_NEVER,
                'body' => [DirectUrlMessageFactory::CREATE_REDIRECT => false],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => false],
            ],
            [
                'strategy' => Configuration::STRATEGY_NEVER,
                'body' => [DirectUrlMessageFactory::CREATE_REDIRECT => true],
                'expected' => [DirectUrlMessageFactory::CREATE_REDIRECT => false],
            ],
        ];
    }

    /**
     * @dataProvider configureRedirectOptionWhenInvalidDataProvider
     */
    public function testConfigureRedirectOptionWhenInvalid(array $body, string $expectedErrorMessage): void
    {
        $resolver = new OptionsResolver();
        $this->helper->configureRedirectOption($resolver);

        $this->expectErrorMessageMatches($expectedErrorMessage);
        $resolver->resolve($body);
    }

    public function configureRedirectOptionWhenInvalidDataProvider(): array
    {
        return [
            [
                'body' => [DirectUrlMessageFactory::CREATE_REDIRECT => new \stdClass()],
                'expectedErrorMessage' => '/The option "createRedirect" with value stdClass is expected'
                    . ' to be of type "bool"/',
            ],
        ];
    }
}
