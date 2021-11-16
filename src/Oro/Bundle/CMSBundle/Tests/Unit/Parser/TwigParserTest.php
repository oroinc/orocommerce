<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Parser;

use Oro\Bundle\CMSBundle\Parser\TwigParser;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Source;
use Twig\TokenStream;

class TwigParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var TwigParser */
    private $parser;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->parser = new TwigParser($this->twig);
    }

    public function testFindFunctionCalls(): void
    {
        $tokenStream = new TokenStream([]);

        $this->twig->expects($this->once())
            ->method('tokenize')
            ->with($this->callback(function ($source) {
                $this->assertInstanceOf(Source::class, $source);
                $this->assertEquals('test content', $source->getCode());
                return true;
            }))
            ->willReturn($tokenStream);

        $this->twig->expects($this->once())
            ->method('parse')
            ->with($tokenStream)
            ->willReturn(new ModuleNode(
                new BodyNode([
                    new FunctionExpression('function_1', new Node([
                        new Node([], ['value' => 'arg_1_1']),
                        new Node([], ['value' => 'arg_1_2']),
                    ]), 1),
                    new FunctionExpression('function_2', new Node([
                        new Node([], ['value' => 'arg_2a']),
                    ]), 1),
                    new FunctionExpression('function_2', new Node([
                        new Node([], ['value' => 'arg_2b']),
                    ]), 1),
                    new FunctionExpression('function_3', new Node(), 1),
                ]),
                new FunctionExpression('function_4', new Node(), 1),
                new Node([]),
                new Node([]),
                new Node([]),
                '',
                new Source('', '')
            ));

        $this->assertSame(
            [
                'function_1' => [
                    ['arg_1_1', 'arg_1_2'],
                ],
                'function_2' => [
                    ['arg_2a'],
                    ['arg_2b'],
                ],
            ],
            $this->parser->findFunctionCalls('test content', ['function_1', 'function_2'])
        );
    }
}
