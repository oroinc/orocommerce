<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This extension is used to validate that content variant collection has no Product Collection variants with the same
 * name (as this case is not covered by UniqueEntity constraint).
 */
class ProductCollectionExtension extends AbstractTypeExtension
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $extendedType;

    /**
     * @param TranslatorInterface $translator
     * @param $extendedType
     */
    public function __construct(TranslatorInterface $translator, $extendedType)
    {
        $this->translator = $translator;
        $this->extendedType = $extendedType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $names = [];
        foreach ($form->all() as $variantForm) {
            if (!$variantForm->has('productCollectionSegment')
                || !$variantForm->get('productCollectionSegment')->has('name')
            ) {
                continue;
            }

            $productCollectionSegment = $variantForm->get('productCollectionSegment');
            $productCollectionSegmentName = $productCollectionSegment
                ->getData()
                ->getNameLowercase();

            if (array_key_exists($productCollectionSegmentName, $names)) {
                $productCollectionSegment->get('name')->addError(new FormError(
                    $this->translator->trans(
                        'oro.product.product_collection.unique_segment_name.message',
                        [],
                        'validators'
                    )
                ));
                break;
            }

            if ($productCollectionSegmentName) {
                $names[$productCollectionSegmentName] = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }
}
