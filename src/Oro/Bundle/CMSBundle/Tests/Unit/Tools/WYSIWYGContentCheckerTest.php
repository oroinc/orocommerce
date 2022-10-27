<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Tools;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\WYSIWYGContentChecker;
use Oro\Bundle\UIBundle\Tools\HTMLPurifier\ErrorCollector;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Template;
use Twig\TemplateWrapper;

class WYSIWYGContentCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var HTMLPurifierScopeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlPurifierScopeProvider;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var WYSIWYGContentChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->htmlPurifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->twig = $this->createMock(Environment::class);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function (string $key, array $params) {
                    return sprintf(
                        '%s %s',
                        $key,
                        implode(
                            ', ',
                            array_map(
                                static function (string $value, string $key) {
                                    return sprintf('%s => %s', $key, $value);
                                },
                                array_values($params),
                                array_keys($params)
                            )
                        )
                    );
                }
            );

        $this->checker = new WYSIWYGContentChecker(
            $this->htmlPurifierScopeProvider,
            $this->htmlTagHelper,
            $this->twig,
            $this->translator
        );
    }

    public function testCheck(): void
    {
        $content = 'test';
        $className = Page::class;
        $fieldName = 'content';
        $scope = 'default';

        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->with($content)
            ->willThrowException(new Error('test message', 42));

        $this->htmlPurifierScopeProvider->expects($this->once())
            ->method('getScope')
            ->with($className, $fieldName)
            ->willReturn($scope);

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($content, $scope)
            ->willReturnArgument(0);

        $errorCollector = $this->createMock(ErrorCollector::class);
        $errorCollector->expects($this->atLeastOnce())
            ->method('getRaw')
            ->willReturn(
                [
                    [
                        ErrorCollector::LINENO => 1001,
                        ErrorCollector::MESSAGE => 'message 1',
                    ],
                    [
                        ErrorCollector::LINENO => 2002,
                        ErrorCollector::MESSAGE => 'message 2',
                    ],
                ]
            );

        $this->htmlTagHelper->expects($this->once())
            ->method('getLastErrorCollector')
            ->willReturn($errorCollector);

        $this->assertEquals(
            [
                [
                    'message' => 'oro.cms.wysiwyg.formatted_twig_error_line {{ line }} => 42, {{ twig escaping link ' .
                        '}} => <a href="https://twig.symfony.com/doc/2.x/templates.html#escaping" target="_blank">' .
                        'oro.cms.wysiwyg.twig_escaping_link_text </a>',
                    'line' => 42,
                ],
                [
                    'message' => 'oro.htmlpurifier.formatted_error_line {{ line }} => 1001, {{ message }} => message 1',
                    'messageRaw' => 'message 1',
                    'line' => 1001,
                ],
                [
                    'message' => 'oro.htmlpurifier.formatted_error_line {{ line }} => 2002, {{ message }} => message 2',
                    'messageRaw' => 'message 2',
                    'line' => 2002,
                ],
            ],
            $this->checker->check($content, $className, $fieldName)
        );
    }

    public function testCheckWithoutScope(): void
    {
        $content = 'test';
        $className = Page::class;
        $fieldName = 'content';

        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->with($content)
            ->willThrowException(new Error('test message', 42));

        $this->htmlPurifierScopeProvider->expects($this->once())
            ->method('getScope')
            ->with($className, $fieldName)
            ->willReturn(null);

        $this->htmlTagHelper->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            [
                [
                    'message' => 'oro.cms.wysiwyg.formatted_twig_error_line {{ line }} => 42, {{ twig escaping link ' .
                        '}} => <a href="https://twig.symfony.com/doc/2.x/templates.html#escaping" target="_blank">' .
                        'oro.cms.wysiwyg.twig_escaping_link_text </a>',
                    'line' => 42,
                ],
            ],
            $this->checker->check($content, $className, $fieldName)
        );
    }

    public function testCheckWithoutErrorCollector(): void
    {
        $content = 'test';
        $className = Page::class;
        $fieldName = 'content';
        $scope = 'default';

        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->with($content)
            ->willThrowException(new Error('test message', 42));

        $this->htmlPurifierScopeProvider->expects($this->once())
            ->method('getScope')
            ->with($className, $fieldName)
            ->willReturn($scope);

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($content, $scope)
            ->willReturnArgument(0);

        $this->htmlTagHelper->expects($this->once())
            ->method('getLastErrorCollector')
            ->willReturn(null);

        $this->assertEquals(
            [
                [
                    'message' => 'oro.cms.wysiwyg.formatted_twig_error_line {{ line }} => 42, {{ twig escaping link ' .
                        '}} => <a href="https://twig.symfony.com/doc/2.x/templates.html#escaping" target="_blank">' .
                        'oro.cms.wysiwyg.twig_escaping_link_text </a>',
                    'line' => 42,
                ],
            ],
            $this->checker->check($content, $className, $fieldName)
        );
    }

    public function testCheckWithoutErrors(): void
    {
        $content = 'test';
        $className = Page::class;
        $fieldName = 'content';
        $scope = 'default';

        $template = $this->createMock(Template::class);
        $template->expects($this->once())
            ->method('render')
            ->willReturn($content);

        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->with($content)
            ->willReturn(new TemplateWrapper($this->twig, $template));

        $this->htmlPurifierScopeProvider->expects($this->once())
            ->method('getScope')
            ->with($className, $fieldName)
            ->willReturn($scope);

        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with($content, $scope)
            ->willReturnArgument(0);

        $errorCollector = $this->createMock(ErrorCollector::class);
        $errorCollector->expects($this->atLeastOnce())
            ->method('getRaw')
            ->willReturn([]);

        $this->htmlTagHelper->expects($this->once())
            ->method('getLastErrorCollector')
            ->willReturn($errorCollector);

        $this->assertEquals([], $this->checker->check($content, $className, $fieldName));
    }
}
