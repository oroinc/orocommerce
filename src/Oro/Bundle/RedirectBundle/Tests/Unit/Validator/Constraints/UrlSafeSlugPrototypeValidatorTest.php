<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Validator\Constraints\UrlSafeSlugPrototype;
use Oro\Bundle\RedirectBundle\Validator\Constraints\UrlSafeSlugPrototypeValidator;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UrlSafeSlugPrototypeValidatorTest extends ConstraintValidatorTestCase
{
    private const ERROR_MESSAGE = 'This value should contain only latin letters, numbers and symbols "-._~".';

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $externalValidator;

    protected function setUp(): void
    {
        $this->externalValidator = $this->createMock(ValidatorInterface::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new UrlSafeSlugPrototypeValidator($this->externalValidator);
    }

    public function testValidateWhenNull(): void
    {
        $this->validator->validate(null, new UrlSafeSlugPrototype());

        $this->assertNoViolation();
    }

    public function testValidateWhenValidPrototype(): void
    {
        $this->externalValidator
            ->expects(self::once())
            ->method('validate')
            ->with('some_value', new UrlSafe())
            ->willReturn(new ConstraintViolationList());

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('some_value');
        $this->validator->validate($slugPrototype, new UrlSafeSlugPrototype());

        $this->assertNoViolation();
    }

    public function testValidateWhenInvalidPrototype(): void
    {
        $this->externalValidator
            ->expects(self::once())
            ->method('validate')
            ->with('inval,id', new UrlSafe())
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation(self::ERROR_MESSAGE, '', [], '', '', '')
            ]));

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('inval,id');
        $this->validator->validate($slugPrototype, new UrlSafeSlugPrototype());

        $this->buildViolation(self::ERROR_MESSAGE)->assertRaised();
    }

    public function testValidateWhenValidPrototypeWithSlashes(): void
    {
        $this->externalValidator
            ->expects(self::once())
            ->method('validate')
            ->with('some_value1/some_value2', new UrlSafe(['allowSlashes' => true]))
            ->willReturn(new ConstraintViolationList());

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('some_value1/some_value2');
        $this->validator->validate($slugPrototype, new UrlSafeSlugPrototype(['allowSlashes' => true]));

        $this->assertNoViolation();
    }

    public function testValidateWhenInvalidPrototypeWithSlashes(): void
    {
        $this->externalValidator
            ->expects(self::once())
            ->method('validate')
            ->with('valid/inval,id', new UrlSafe(['allowSlashes' => true]))
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation(self::ERROR_MESSAGE, '', [], '', '', '')
            ]));

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('valid/inval,id');
        $this->validator->validate($slugPrototype, new UrlSafeSlugPrototype(['allowSlashes' => true]));

        $this->buildViolation(self::ERROR_MESSAGE)->assertRaised();
    }
}
