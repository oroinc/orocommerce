<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Twig;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;

use Oro\Bundle\AsseticBundle\AssetsConfiguration;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\Twig\DemoThemeExtension;
use OroB2B\Bundle\FrontendBundle\Twig\FrontendAsseticTokenParser;

class DemoThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AssetsConfiguration
     */
    protected $assetsConfiguration;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AssetFactory
     */
    protected $assetFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @var DemoThemeExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->assetsConfiguration = $this->getMockBuilder('Oro\Bundle\AsseticBundle\AssetsConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetFactory = $this->getMockBuilder('Symfony\Bundle\AsseticBundle\Factory\AssetFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->themeRegistry = $this->getMockBuilder('Oro\Bundle\ThemeBundle\Model\ThemeRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DemoThemeExtension(
            $this->assetFactory,
            $this->assetsConfiguration,
            $this->themeRegistry
        );
    }

    public function testGetName()
    {
        $this->assertEquals(DemoThemeExtension::NAME, $this->extension->getName());
    }

    public function testGetTokenParsers()
    {
        $parsers = $this->extension->getTokenParsers();
        $this->assertInternalType('array', $parsers);
        $this->assertCount(1, $parsers);

        /** @var FrontendAsseticTokenParser $parser */
        $parser = $parsers[0];
        $this->assertInstanceOf('OroB2B\Bundle\FrontendBundle\Twig\FrontendAsseticTokenParser', $parser);

        $this->assertEquals(DemoThemeExtension::TAG_NAME, $parser->getTag());
        $this->assertAttributeSame($this->assetsConfiguration, 'assetsConfiguration', $parser);
        $this->assertAttributeSame($this->assetFactory, 'assetFactory', $parser);
        $this->assertAttributeSame($this->themeRegistry, 'themeRegistry', $parser);
        $this->assertAttributeSame(DemoThemeExtension::PARSER_OUTPUT, 'output', $parser);
    }
}
