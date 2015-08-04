<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

class ProductUnitRoundingTypeType extends AbstractType
{
    const NAME = 'orob2b_product_unit_rounding_type';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    RoundingService::HALF_UP => $this->translator->trans('orob2b.product.rounding.type.half_up.label'),
                    RoundingService::HALF_DOWN =>
                        $this->translator->trans('orob2b.product.rounding.type.half_down.label'),
                    RoundingService::CEIL => $this->translator->trans('orob2b.product.rounding.type.ceil.label'),
                    RoundingService::FLOOR => $this->translator->trans('orob2b.product.rounding.type.floor.label'),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
