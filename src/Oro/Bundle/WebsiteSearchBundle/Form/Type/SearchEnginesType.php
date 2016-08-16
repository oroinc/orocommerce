<?php

namespace Oro\Bundle\WebsiteSearchBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WebsiteSearchBundle\Provider\SearchEnginesProvider;

class SearchEnginesType extends AbstractType
{
    const NAME = 'oro_search_engines_type';

    /**
     * @var SearchEnginesProvider
     */
    protected $provider;

    /**
     * @param SearchEnginesProvider $provider
     */
    public function __construct(SearchEnginesProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = $this->provider->getEngines();

        $resolver->setDefaults(['choices' => $choices]);
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}