<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContent;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContentValidator;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Template;
use Twig\TemplateWrapper;

class TwigContentValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var TwigContentValidator */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $this->validator = new TwigContentValidator($this->twig);
    }

    public function testValidateValidValue(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';

        $template = $this->createMock(Template::class);
        $template->expects($this->once())
            ->method('render')
            ->willReturn($value);

        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->with($value)
            ->willReturn(new TemplateWrapper($this->twig, $template));

        /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->never())
            ->method('addViolation');

        $constraint = new TwigContent();
        $this->validator->initialize($context);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateInvalidValue(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';

        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->with($value)
            ->willThrowException(new Error(''));

        /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContext::class);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $constraint = new TwigContent();

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message, [])
            ->willReturn($violationBuilder);

        $this->validator->initialize($context);
        $this->validator->validate($value, $constraint);
    }
}
