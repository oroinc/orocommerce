<?php

namespace OroB2B\Bundle\WebsiteBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class WebsiteSelectExtension extends AbstractTypeExtension
{
    /**
     * @var string
     */
    protected $extendedType = 'orob2b_order_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'website',
            'entity',
            [
                'class' => 'OroB2B\Bundle\WebsiteBundle\Entity\Website',
                'label' => 'orob2b.order.website.label'
            ]
        );
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * @param string $extendedType
     * @return WebsiteSelectExtension
     */
    public function setExtendedType($extendedType)
    {
        $this->extendedType = $extendedType;

        return $this;
    }
}
