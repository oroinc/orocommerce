<?php

namespace Oro\Bundle\ProductBundle\Form\EventSubscriber;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductImageTypesSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThemeImageType[]
     */
    private $imageTypes;

    /**
     * @param ThemeImageType[] $imageTypes
     */
    public function __construct(array $imageTypes)
    {
        $this->imageTypes = $imageTypes;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * Add dynamic radio/checkbox controls for available image types
     */
    public function postSetData(FormEvent $event)
    {
        /** @var ProductImage $productImage */
        $productImage = $event->getForm()->getData();

        foreach ($this->imageTypes as $imageType) {
            $isRadioButton = $imageType->getMaxNumber() === 1;

            $event->getForm()->add(
                $imageType->getName(),
                $isRadioButton ? RadioType::class : CheckboxType::class,
                [
                    'label' => $imageType->getLabel(),
                    'value' => 1,
                    'mapped' => false,
                    'data' => $productImage ? $productImage->hasType($imageType->getName()) : false,
                ]
            );
        }
    }

    /**
     * Converts data from dynamic image type controls to types array
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $types = [];

        foreach ($this->imageTypes as $imageType) {
            $imageTypeName = $imageType->getName();

            if (isset($data[$imageTypeName])) {
                $types[] = $imageTypeName;
            }
        }

        $data['types'] = $types;
        $event->setData($data);
    }
}
