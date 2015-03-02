<?php

namespace OroB2B\Bundle\FallbackBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\FallbackBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer;

class LocalizedFallbackValueCollectionType extends AbstractType
{
    const NAME = 'orob2b_fallback_localized_value_collection';

    const FIELD_VALUES = 'values';
    const FIELD_IDS    = 'ids';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::FIELD_VALUES,
            LocalizedPropertyType::NAME,
            ['type' => $options['type'], 'options' => $options['options']]
        )->add(
            self::FIELD_IDS,
            'collection',
            ['type' => 'hidden']
        );

        $builder->addViewTransformer(
            new LocalizedFallbackValueCollectionTransformer($this->registry, $options['field'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'field'   => 'string', // field used to store data - string or text
            'type'    => 'text',   // value form type
            'options' => [],       // value form options
        ]);
    }
}
