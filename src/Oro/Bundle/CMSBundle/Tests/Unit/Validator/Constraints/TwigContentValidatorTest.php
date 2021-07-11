<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContent;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContentValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Template;
use Twig\TemplateWrapper;

class TwigContentValidatorTest extends ConstraintValidatorTestCase
{
    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new TwigContentValidator($this->twig);
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

        $constraint = new TwigContent();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalidValue(): void
    {
        $value = '<div><h1>Hello World!</h1></div>';

        $this->twig->expects($this->once())
            ->method('createTemplate')
            ->with($value)
            ->willThrowException(new Error(''));

        $constraint = new TwigContent();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
