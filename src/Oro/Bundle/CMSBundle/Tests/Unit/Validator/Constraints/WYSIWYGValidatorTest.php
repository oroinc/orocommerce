<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGValidator;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class WYSIWYGValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var WYSIWYGValidator */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagHelper = new HtmlTagHelper($this->htmlTagProvider);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->validator = new WYSIWYGValidator($htmlTagHelper, $this->logger);
    }

    public function testValidateValidValue(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';

        /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->never())
            ->method('addViolation');

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $this->htmlTagProvider
            ->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn(['div', 'h1']);

        $constraint = new WYSIWYG();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateInvalidValue(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';
        $constraint = new WYSIWYG();

        /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContext::class);
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message, [])
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->logger
            ->expects($this->exactly(4))
            ->method('debug')
            ->withConsecutive(
                ['WYSIWYG validation error: Unrecognized <div> tag removed', [
                    'line' => 1,
                    'severity' => 1
                ]],
                ['WYSIWYG validation error: Unrecognized <h1> tag removed', [
                    'line' => 1,
                    'severity' => 1
                ]],
                ['WYSIWYG validation error: Unrecognized </h1> tag removed', [
                    'line' => 1,
                    'severity' => 1
                ]],
                ['WYSIWYG validation error: Unrecognized </div> tag removed', [
                    'line' => 1,
                    'severity' => 1
                ]]
            );

        $this->htmlTagProvider
            ->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn([]);

        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }
}
