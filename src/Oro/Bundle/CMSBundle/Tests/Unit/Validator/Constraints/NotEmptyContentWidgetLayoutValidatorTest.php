<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Oro\Bundle\CMSBundle\Validator\Constraints\NotEmptyContentWidgetLayout;
use Oro\Bundle\CMSBundle\Validator\Constraints\NotEmptyContentWidgetLayoutValidator;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyContentWidgetLayoutValidatorTest extends ConstraintValidatorTestCase
{
    private ContentWidgetLayoutProvider|\PHPUnit\Framework\MockObject\MockObject $contentWidgetLayoutProvider;

    protected function createValidator(): NotEmptyContentWidgetLayoutValidator
    {
        $this->contentWidgetLayoutProvider = $this->createMock(ContentWidgetLayoutProvider::class);

        return new NotEmptyContentWidgetLayoutValidator($this->contentWidgetLayoutProvider);
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, NotEmptyContentWidgetLayout::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, ContentWidget::class));

        $constraint = new NotEmptyContentWidgetLayout();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnsupportedWhenContentWidgetNull(): void
    {
        $constraint = new NotEmptyContentWidgetLayout();

        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getValidateNoViolations
     */
    public function testValidateNoViolations(ContentWidget $contentWidget): void
    {
        $constraint = new NotEmptyContentWidgetLayout();

        $this->validator->validate($contentWidget, $constraint);

        $this->assertNoViolation();
    }

    public function getValidateNoViolations(): array
    {
        return [
            'ContentWidget without widget type, without layout' => [
                'contentWidget' => new ContentWidget(),
            ],
            'ContentWidget with layout, without widget type' => [
                'contentWidget' => (new ContentWidget())->setLayout('foo'),
            ],
            'no possible layout values' => [
                'contentWidget' => (new ContentWidget())->setWidgetType('bar'),
            ],
        ];
    }

    public function testValidateWhenEmptyLayoutAndNotEmptyPossibleLayoutValues(): void
    {
        $contentWidget = (new ContentWidget())
            ->setName('content widget name')
            ->setWidgetType('bar');

        $constraint = new NotEmptyContentWidgetLayout();

        $this->contentWidgetLayoutProvider->expects(self::once())
            ->method('getWidgetLayouts')
            ->with('bar')
            ->willReturn(
                [
                    'first' => 'oro.widget.layout.first.label',
                    'second' => 'oro.widget.layout.second.label',
                ]
            );

        $this->validator->validate($contentWidget, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'object')
            ->atPath('property.path.layout')
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->assertRaised();
    }
}
