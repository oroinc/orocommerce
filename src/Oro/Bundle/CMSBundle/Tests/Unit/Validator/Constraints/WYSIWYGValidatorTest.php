<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGValidator;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WYSIWYGValidatorTest extends ConstraintValidatorTestCase
{
    /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagProvider;

    /** @var HTMLPurifierScopeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $purifierScopeProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $this->purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        parent::setUp();

        $this->setObject(new Page());
        $this->setPropertyPath('content');
    }

    protected function createValidator()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $parameters) {
                if ('oro.htmlpurifier.messages.Strategy_RemoveForeignElements: Foreign element removed' === $id) {
                    return 'Unrecognized $CurrentToken.Serialized tag removed';
                }
                if ('oro.htmlpurifier.formatted_error' === $id) {
                    return strtr('- {{ message }} (near {{ place }}...)', $parameters);
                }

                return $id . ' (translated)';
            });

        $htmlTagHelper = new HtmlTagHelper($this->htmlTagProvider);
        $htmlTagHelper->setTranslator($translator);

        return new WYSIWYGValidator(
            $htmlTagHelper,
            $this->purifierScopeProvider,
            $translator,
            $this->logger
        );
    }

    public function testValidateEmptyValue(): void
    {
        $value = '';

        $this->purifierScopeProvider->expects($this->never())
            ->method('getScope');

        $constraint = new WYSIWYG();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateValidValue(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';

        $this->purifierScopeProvider->expects($this->once())
            ->method('getScope')
            ->with(Page::class, 'content')
            ->willReturn('default');

        $this->htmlTagProvider->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn(['div', 'h1']);

        $this->logger->expects($this->never())
            ->method('debug');

        $constraint = new WYSIWYG();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalidValue(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';

        $this->purifierScopeProvider->expects($this->once())
            ->method('getScope')
            ->with(Page::class, 'content')
            ->willReturn('default');

        $this->htmlTagProvider->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn([]);

        $this->expectsDebugLogs();

        $constraint = new WYSIWYG();
        $this->validator->validate($value, $constraint);

        $this->assertViolationRaised($constraint, 'content');
    }

    public function testValidateByPropertyPath(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';
        $propertyPath = 'data.content';

        $this->purifierScopeProvider->expects($this->once())
            ->method('getScope')
            ->with(Page::class, 'content')
            ->willReturn('default');

        $this->htmlTagProvider->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn([]);

        $this->expectsDebugLogs();

        $constraint = new WYSIWYG();
        $this->setPropertyPath($propertyPath);
        $this->validator->validate($value, $constraint);

        $this->assertViolationRaised($constraint, $propertyPath);
    }

    private function expectsDebugLogs(): void
    {
        $this->logger->expects($this->exactly(4))
            ->method('debug')
            ->withConsecutive(
                [
                    'WYSIWYG validation error: Unrecognized <div> tag removed',
                    ['line' => 1, 'severity' => 1]
                ],
                [
                    'WYSIWYG validation error: Unrecognized <h1> tag removed',
                    ['line' => 1, 'severity' => 1]
                ],
                [
                    'WYSIWYG validation error: Unrecognized </h1> tag removed',
                    ['line' => 1, 'severity' => 1]
                ],
                [
                    'WYSIWYG validation error: Unrecognized </div> tag removed',
                    ['line' => 1, 'severity' => 1]
                ]
            );
    }

    private function assertViolationRaised(WYSIWYG $constraint, string $propertyPath): void
    {
        $this->buildViolation($constraint->message)
            ->setParameter(
                '{{ errorsList }}',
                '- Unrecognized <div> tag removed (near <div><h1>Hello World!</h1...);' . "\n"
                . '- Unrecognized <h1> tag removed (near <h1>Hello World!</h1></di...);' . "\n"
                . '- Unrecognized </h1> tag removed (near </h1></div>...);' . "\n"
                . '- Unrecognized </div> tag removed (near </div>...)'
            )
            ->atPath($propertyPath)
            ->assertRaised();
    }
}
