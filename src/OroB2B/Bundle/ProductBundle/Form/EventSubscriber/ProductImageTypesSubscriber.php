<?php

namespace OroB2B\Bundle\ProductBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Component\Layout\Extension\Theme\Model\ThemeImageType;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Entity\ProductImageType;

class ProductImageTypesSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThemeImageType[]
     */
    private $imageTypes;

    /**
     * @var ProductImageType[]
     */
    private $productImageTypes;

    private $submittedImageTypes;

    /**
     * @param EntityRepository $productImageTypeRepository
     * @param ThemeImageType[] $imageTypes
     */
    public function __construct(EntityRepository $productImageTypeRepository, array $imageTypes)
    {
        $this->imageTypes = $imageTypes;
        $this->productImageTypes = $this->getIndexedProductImageTypes($productImageTypeRepository);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::POST_SUBMIT  => 'postSubmit',
        ];
    }

    /**
     * Add dynamic radio/checkbox controls for available image types
     *
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        /** @var ProductImage $productImage */
        $productImage = $event->getForm()->getData();

        foreach ($this->imageTypes as $imageType) {
            $isRadioButton = $imageType->getMaxNumber() === 1;

            $event->getForm()->add(
                $imageType->getName(),
                $isRadioButton ? 'radio' : 'checkbox',
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
     *
     * @param FormEvent $event
     */
    public function PreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $types = [];

        foreach ($this->imageTypes as $imageType) {
            $imageTypeName = $imageType->getName();

            if (isset($data[$imageTypeName]) && isset($this->productImageTypes[$imageTypeName])) {
                $types[] = $this->productImageTypes[$imageTypeName];

            }
        }

        $this->submittedImageTypes = new ArrayCollection($types);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var ProductImage $productImage */
        $productImage = $event->getData();

        foreach ($productImage->getTypes() as $imageType) {
            $productImage->removeType($imageType);
        }

        foreach ($this->submittedImageTypes as $submittedImageType) {
            $productImage->addType($submittedImageType);
        }
    }


    /**
     * @param EntityRepository $productImageTypeRepository
     * @return ProductImageType[]
     */
    private function getIndexedProductImageTypes(EntityRepository $productImageTypeRepository)
    {
        $types = [];

        /** @var ProductImageType $productImageType */
        foreach ($productImageTypeRepository->findAll() as $productImageType) {
            $types[$productImageType->getType()] = $productImageType;
        }

        return $types;
    }
}
