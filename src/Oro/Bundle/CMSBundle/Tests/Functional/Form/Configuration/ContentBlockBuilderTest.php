<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Functional\Form\Configuration;

use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentBlockData;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormFactoryInterface;

class ContentBlockBuilderTest extends WebTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadContentBlockData::class]);

        $this->formFactory = self::getContainer()->get('form.factory');
    }

    /**
     * @dataProvider getPreSetDefaultValueDataProvider
     */
    public function testPreSetDefaultValue(string $optionKey, string|null $expectedDefaultValue): void
    {
        $form = $this->formFactory->create(ThemeConfigurationType::class, new ThemeConfiguration());

        self::assertEquals($expectedDefaultValue, $form->get('configuration')->get($optionKey)->getData());
    }

    public function getPreSetDefaultValueDataProvider(): array
    {
        return [
            [LayoutThemeConfiguration::buildOptionKey('header', 'promotional_content'), null],
        ];
    }

    /**
     * @dataProvider getShowSavedValueDataProvider
     */
    public function testShowSavedValue(string|null $contentBlockReference): void
    {
        $optionKey = LayoutThemeConfiguration::buildOptionKey('header', 'promotional_content');
        $savedValue = $contentBlockReference ? $this->getReference($contentBlockReference) : $contentBlockReference;
        $themeConfiguration = (new ThemeConfiguration())
            ->addConfigurationOption($optionKey, $savedValue);
        ReflectionUtil::setPropertyValue($themeConfiguration, 'id', 1);

        $form = $this->formFactory->create(ThemeConfigurationType::class, $themeConfiguration);

        self::assertEquals($savedValue, $form->get('configuration')->get($optionKey)->getData());
    }

    public function getShowSavedValueDataProvider(): array
    {
        return [
            ['content_block_1'],
            [null],
        ];
    }
}
