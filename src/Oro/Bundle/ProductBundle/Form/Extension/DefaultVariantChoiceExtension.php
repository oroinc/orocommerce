<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\DefaultVariantChoiceType;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Form extension adds submitted default variant value as a choice for the choice form type to allow
 * for dynamically adding select options on front end
 */
class DefaultVariantChoiceExtension extends AbstractTypeExtension
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if ($form->has(DefaultVariantChoiceType::DEFAULT_VARIANT_FORM_FIELD)
            && isset($data[DefaultVariantChoiceType::DEFAULT_VARIANT_FORM_FIELD])
        ) {
            $selectedId = $data[DefaultVariantChoiceType::DEFAULT_VARIANT_FORM_FIELD];
            if (empty($selectedId)) {
                return;
            }

            $selectedProduct = $this->registry->getManagerForClass(Product::class)
                ->getRepository(Product::class)
                ->find($selectedId);

            if (null === $selectedProduct) {
                return;
            }

            FormUtils::replaceField(
                $form,
                DefaultVariantChoiceType::DEFAULT_VARIANT_FORM_FIELD,
                ['choices'  => [$selectedProduct]]
            );
        }
    }
}
