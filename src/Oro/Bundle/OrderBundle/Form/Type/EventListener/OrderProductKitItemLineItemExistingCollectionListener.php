<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Modifies a kit item line items collection form according to the already existing data.
 */
class OrderProductKitItemLineItemExistingCollectionListener implements EventSubscriberInterface
{
    private string $entryFormType;

    private array $entryOptions;

    /**
     * @param string $entryFormType Kit item line item form type to use when adding an element to collection.
     */
    public function __construct(string $entryFormType, array $entryOptions)
    {
        $this->entryFormType = $entryFormType;
        $this->entryOptions = $entryOptions;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => [['overrideRequiredOnPreSetData'], ['addMissingElementsOnPreSetData']],
        ];
    }

    /**
     * Overrides "required" option of kit item line item form fields in the already existing collection as following:
     *  - all kit item line items that are present in the already existing collection must follow their own
     *      "optional" flag;
     *  - all kit item line items that are not present in the already existing collection must be treated as optional.
     */
    public function overrideRequiredOnPreSetData(FormEvent $event): void
    {
        /** @var Collection<OrderProductKitItemLineItem>|null $collection */
        $collection = $event->getData();
        $isExisting = $collection instanceof PersistentCollection;

        $form = $event->getForm();
        foreach ($form as $key => $child) {
            if (isset($collection[$key])) {
                // Overrides "required" option according to the already existing kit item line item.
                FormUtils::replaceField($form, (string)$key, ['required' => !$collection[$key]->isOptional()]);
                continue;
            }

            if ($isExisting) {
                // Overrides "required" option to "false" as all new kit item line items must be treated as optional
                // in the already existing collection.
                FormUtils::replaceField($form, (string)$key, ['required' => false]);
            }
        }
    }

    /**
     * Adds form fields for the kit item line items that exist in a kit item line item collection but are not
     * represented in a form, i.e. when actual product kit does not have such kit item anymore.
     */
    public function addMissingElementsOnPreSetData(FormEvent $event): void
    {
        /** @var Collection<OrderProductKitItemLineItem>|null $collection */
        $collection = $event->getData();
        if (null === $collection) {
            return;
        }

        $form = $event->getForm();

        foreach ($collection as $key => $kitItemLineItem) {
            if ($form->has((string)$key)) {
                continue;
            }

            // Adds element for the already existing kit item line item.
            $form->add(
                (string)$key,
                $this->entryFormType,
                array_replace(
                    [
                        'required' => !$kitItemLineItem->isOptional(),
                        'property_path' => '[' . $key . ']',
                    ],
                    $this->entryOptions
                )
            );
        }
    }
}
