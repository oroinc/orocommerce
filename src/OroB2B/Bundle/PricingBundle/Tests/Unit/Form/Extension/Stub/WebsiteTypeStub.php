<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;

class WebsiteTypeStub extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return WebsiteType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', ['label' => 'orob2b.website.name.label']);
    }
}
