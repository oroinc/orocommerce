<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Parser;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\Parser\TwigContentWidgetParser;
use Oro\Bundle\CMSBundle\Twig\WidgetExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigContentWidgetParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var TwigContentWidgetParser */
    private $parser;

    protected function setUp(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new WidgetExtension($this->createMock(ContentWidgetRenderer::class)));

        $this->parser = new TwigContentWidgetParser($twig);
    }

    public function testParseNames(): void
    {
        $this->assertEquals(
            ['test1', 'test2'],
            $this->parser->parseNames(
                "<p><hr>{{ widget('test1')|trim|nl2br }}</p>{{ source(widget('test2'), widget(), widget('test2')|e) }}"
            )
        );
    }
}
