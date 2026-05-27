<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Extension;

use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sets 'validation_groups' option depending on the order line item entity state.
 * Validates OrderLineItemDraftType on POST_SET_DATA event to add validation errors to the form on initial rendering.
 * Defines 'initial_validation' option to allow skipping validation on POST_SET_DATA when needed.
 */
class ValidateOrderLineItemDraftExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly EntityStateChecker $entityStateChecker,
        private readonly ValidatorInterface $validator,
        private readonly FormRendererInterface $formRenderer,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, $this->validateOnPostSetData(...));
    }

    /**
     * Adds validation errors to the form on POST_SET_DATA event so they are present on initial rendering.
     */
    private function validateOnPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var OrderLineItem|null $orderLineItem */
        $orderLineItem = $event->getData();

        // Skip validation if form is being submitted (validation will happen on POST_SUBMIT)
        if ($form->isSubmitted()) {
            return;
        }

        // Skip validation if initial_validation option is set to false.
        if ($form->getConfig()->getOption('initial_validation') === false) {
            return;
        }

        if (!$orderLineItem instanceof OrderLineItem) {
            return;
        }

        $validationGroups = $this->getValidationGroups($form);

        $violations = $this->validator
            ->startContext($orderLineItem)
            ->atPath('data')
            ->validate($orderLineItem, null, $validationGroups)
            ->getViolations();

        if ($violations->count() === 0) {
            return;
        }

        // Map violations to form fields
        $violationMapper = new ViolationMapper($this->formRenderer, $this->translator);
        foreach ($violations as $violation) {
            $violationMapper->mapViolation($violation, $form);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('initial_validation')
            ->allowedTypes('bool')
            ->default(true);

        $resolver->setDefault('validation_groups', $this->getValidationGroups(...));
    }

    /**
     * @param FormInterface $form
     *
     * @return GroupSequence|array
     */
    private function getValidationGroups(FormInterface $form): GroupSequence|array
    {
        if ($form->get('drySubmitTrigger')->getData()) {
            return ['order_line_item_draft_dry_submit'];
        }

        $validationGroups = new GroupSequence([Constraint::DEFAULT_GROUP]);
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $form->getData();

        if ($this->entityStateChecker->isNewEntity($orderLineItem)) {
            $validationGroups->groups[] = 'order_line_item_create';
        } elseif ($this->entityStateChecker->isChangedEntity($orderLineItem, ['product', 'checksum'])) {
            $validationGroups->groups[] = 'order_line_item_update';
        }

        return $validationGroups;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderLineItemDraftType::class];
    }
}
