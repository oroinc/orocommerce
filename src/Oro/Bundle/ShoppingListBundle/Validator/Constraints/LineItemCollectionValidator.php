<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates LineItemCollection Constraint.
 * Also it dispatches event LineItemValidateEvent before validation.
 *
 * @deprecated since 5.1, use Symfony Validator instead
 */
class LineItemCollectionValidator extends ConstraintValidator
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param [] $value
     * @param Constraint|LineItemCollection $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$this->eventDispatcher->hasListeners(LineItemValidateEvent::NAME)) {
            return;
        }

        $event = new LineItemValidateEvent($value, $constraint->getAdditionalContext());
        $this->eventDispatcher->dispatch($event, LineItemValidateEvent::NAME);

        if ($event->hasErrors()) {
            foreach ($event->getErrors() as $error) {
                $this->context->buildViolation($error['message'])
                    ->atPath($this->createViolationPath($error))
                    ->addViolation();
            }
        }

        if ($event->hasWarnings()) {
            foreach ($event->getWarnings() as $warning) {
                $this->context->buildViolation($warning['message'])
                    ->atPath($this->createViolationPath($warning))
                    ->setCause('warning')
                    ->addViolation();
            }
        }
    }

    private function createViolationPath(array $violationData): string
    {
        $path = sprintf('product.%s.%s', $violationData['sku'], $violationData['unit']);

        if (!empty($violationData['checksum'])) {
            $path .= '.'. $violationData['checksum'];
        }

        return $path;
    }
}
