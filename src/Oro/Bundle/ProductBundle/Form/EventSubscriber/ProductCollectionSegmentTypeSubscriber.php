<?php

namespace Oro\Bundle\ProductBundle\Form\EventSubscriber;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * ProductCollectionSegmentType form event subscriber.
 */
class ProductCollectionSegmentTypeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected array $options = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData'
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $segment = $event->getData();
        if ($segment instanceof Segment && $segment->getId()) {
            // Inject segment in sort order form field for existing segments
            if ($this->options['add_sort_order']) {
                FormUtils::replaceField(
                    $event->getForm(),
                    ProductCollectionSegmentType::SORT_ORDER,
                    [
                        'mapped' => false,
                        'segment' => $segment
                    ]
                );
            }

            // Make segment name required for existing segments
            if ($this->options['add_name_field']) {
                FormUtils::replaceField(
                    $event->getForm(),
                    'name',
                    [
                        'required' => true,
                        'constraints' => [new NotBlank()]
                    ]
                );
            }
        }
    }
}
