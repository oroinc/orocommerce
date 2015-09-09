<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Twig;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;

use Assetic\Asset\AssetCollection;

use Oro\Bundle\AsseticBundle\AssetsConfiguration;
use Oro\Bundle\AsseticBundle\Twig\AsseticTokenParser;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\Twig\FrontendAsseticTokenParser;

class FrontendAsseticTokenParserTest extends \PHPUnit_Framework_TestCase
{
    const ACTIVE_THEME = 'oro';
    const TAG_NAME = 'orob2b_css';
    const PARSER_OUTPUT = 'css/*.css';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AssetsConfiguration
     */
    protected $assetsConfiguration;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AssetFactory
     */
    protected $assetFactory;

    /**
     * @var ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @var []
     */
    protected $themes = [
        self::ACTIVE_THEME => [],
        FrontendAsseticTokenParser::FRONTEND_THEME => [],
    ];

    /**
     * @var AsseticTokenParser
     */
    protected $parser;

    protected function setUp()
    {
        $this->assetsConfiguration = $this->getMockBuilder('Oro\Bundle\AsseticBundle\AssetsConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetFactory = $this->getMockBuilder('Symfony\Bundle\AsseticBundle\Factory\AssetFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->themeRegistry = new ThemeRegistry($this->themes);
        $this->themeRegistry->setActiveTheme(self::ACTIVE_THEME);

        $this->parser = new FrontendAsseticTokenParser(
            $this->assetsConfiguration,
            $this->assetFactory,
            $this->themeRegistry,
            self::TAG_NAME,
            self::PARSER_OUTPUT
        );
    }
    
    public function testParse()
    {
        // fixture token and stream
        $startToken = new \Twig_Token(\Twig_Token::NAME_TYPE, self::TAG_NAME, 31);
        $stream = new \Twig_TokenStream(
            array(
                new \Twig_Token(\Twig_Token::NAME_TYPE, 'filter', 31),
                new \Twig_Token(\Twig_Token::OPERATOR_TYPE, '=', 31),
                new \Twig_Token(\Twig_Token::STRING_TYPE, 'cssrewrite, lessphp, ?cssmin', 31),
                new \Twig_Token(\Twig_Token::NAME_TYPE, 'debug', 31),
                new \Twig_Token(\Twig_Token::OPERATOR_TYPE, '=', 31),
                new \Twig_Token(\Twig_Token::NAME_TYPE, 'false', 31),
                new \Twig_Token(\Twig_Token::NAME_TYPE, 'combine', 31),
                new \Twig_Token(\Twig_Token::OPERATOR_TYPE, '=', 31),
                new \Twig_Token(\Twig_Token::NAME_TYPE, 'false', 31),
                new \Twig_Token(\Twig_Token::NAME_TYPE, 'output', 31),
                new \Twig_Token(\Twig_Token::OPERATOR_TYPE, '=', 31),
                new \Twig_Token(\Twig_Token::STRING_TYPE, 'css/demo.css', 31),
                new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', 31),
                new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', 32),
                new \Twig_Token(\Twig_Token::BLOCK_START_TYPE, '', 33),
                new \Twig_Token(\Twig_Token::NAME_TYPE, 'end' . self::TAG_NAME, 33),
                new \Twig_Token(\Twig_Token::BLOCK_END_TYPE, '', 33),
                new \Twig_Token(\Twig_Token::EOF_TYPE, '', 31),
            )
        );

        $bodyNode = $this->getMockBuilder('\Twig_Node')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Parser $parser */
        $parser = $this->getMockBuilder('Twig_Parser')
            ->disableOriginalConstructor()
            ->getMock();
        $parser->expects($this->once())
            ->method('subparse')
            ->will($this->returnValue($bodyNode));
        $parser->expects($this->once())
            ->method('getStream')
            ->will($this->returnValue($stream));

        $this->assetFactory->expects($this->exactly(2))
            ->method('createAsset')
            ->willReturnCallback(
                function () {
                    // frontend theme during assets compilation
                    $this->assertEquals(
                        FrontendAsseticTokenParser::FRONTEND_THEME,
                        $this->themeRegistry->getActiveTheme()->getName()
                    );

                    return new AssetCollection();
                }
            );

        $this->assetsConfiguration->expects($this->at(0))
            ->method('getCssFiles')
            ->with(false)
            ->willReturn([]);
        $this->assetsConfiguration->expects($this->at(1))
            ->method('getCssFiles')
            ->with(true)
            ->willReturn([]);

        // active theme before parsing
        $this->assertEquals(self::ACTIVE_THEME, $this->themeRegistry->getActiveTheme()->getName());

        $this->parser->setParser($parser);
        $resultNode = $this->parser->parse($startToken);

        // active theme after parsing
        $this->assertEquals(self::ACTIVE_THEME, $this->themeRegistry->getActiveTheme()->getName());

        $this->assertEquals(31, $resultNode->getLine());
        $nodes = $resultNode->getIterator()->getArrayCopy();
        $this->assertCount(2, $nodes);
    }
}
