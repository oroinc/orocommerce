<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Modifies a kit item line items collection form according to the already existing data.
 */
class QuoteProductKitItemLineItemExistingCollectionListener implements EventSubscriberInterface
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
            FormEvents::PRE_SET_DATA => [['addMissingElementsOnPreSetData']],
        ];
    }

    /**
     * Adds form fields for the kit item line items that exist in a kit item line item collection but are not
     * represented in a form, i.e. when actual product kit does not have such kit item anymore.
     */
    public function addMissingElementsOnPreSetData(FormEvent $event): void
    {
        /** @var Collection<QuoteProductKitItemLineItem>|null $collection */
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
            // Overrides "required" option to "false" as all not actual kit item line items must be treated as optional
            // in the already existing collection.
            $form->add(
                (string)$key,
                $this->entryFormType,
                array_replace(
                    [
                        'required' => false,
                        'property_path' => '[' . $key . ']',
                    ],
                    $this->entryOptions
                )
            );
        }
    }
}
