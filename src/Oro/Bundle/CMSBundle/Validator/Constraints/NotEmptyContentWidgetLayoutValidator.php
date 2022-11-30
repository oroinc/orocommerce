<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * ContentWidget should have not empty layout when possible values are not empty.
 */
class NotEmptyContentWidgetLayoutValidator extends ConstraintValidator
{
    private ContentWidgetLayoutProvider $widgetLayoutProvider;

    public function __construct(ContentWidgetLayoutProvider $widgetLayoutProvider)
    {
        $this->widgetLayoutProvider = $widgetLayoutProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotEmptyContentWidgetLayout) {
            throw new UnexpectedTypeException($constraint, NotEmptyContentWidgetLayout::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof ContentWidget) {
            throw new UnexpectedValueException($value, ContentWidget::class);
        }

        if ($value->getLayout() || !$value->getWidgetType()) {
            return;
        }

        $possibleLayoutValues = $this->widgetLayoutProvider->getWidgetLayouts($value->getWidgetType());
        if (!empty($possibleLayoutValues)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(NotBlank::IS_BLANK_ERROR)
                ->atPath('layout')
                ->addViolation();
        }
    }
}
