<?php

namespace Oro\Bundle\CatalogBundle\Form\Type\Filter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SubcategoryFilterType extends AbstractType
{
    const TYPE_INCLUDE = '';
    const TYPE_NOT_INCLUDE = 1;

    const NAME = 'oro_type_subcategory_filter';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
         return FilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $operatorChoices = [
            self::TYPE_INCLUDE => $this->translator->trans('oro.catalog.filter.subcategory.type.include'),
            self::TYPE_NOT_INCLUDE => $this->translator->trans('oro.catalog.filter.subcategory.type.not_include'),
        ];

        $resolver->setDefaults(
            [
                'operator_choices' => $operatorChoices,
                'field_type' => 'entity',
                'field_options' => [
                    'multiple' => true,
                    'class' => Category::class,
                ],
                'categories' => []
            ]
        );

        // this normalizer allows to add/override field_options options outside
        $resolver->setNormalizer(
            'field_options',
            function (Options $options, $value) {
                $value['choices'] = $options['categories'] ?? [];

                return $value;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
