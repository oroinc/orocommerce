<?php

namespace Oro\Bundle\SEOBundle\Form\Type;

use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SitemapChangefreqType extends AbstractType
{
    const NAME = 'oro_sitemap_changefreq';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'oro.seo.system_configuration.fields.changefreq.choice.always.label'
                    => Configuration::CHANGEFREQ_ALWAYS,
                'oro.seo.system_configuration.fields.changefreq.choice.hourly.label'
                    => Configuration::CHANGEFREQ_HOURLY,
                'oro.seo.system_configuration.fields.changefreq.choice.daily.label'
                    => Configuration::CHANGEFREQ_DAILY,
                'oro.seo.system_configuration.fields.changefreq.choice.weekly.label'
                    => Configuration::CHANGEFREQ_WEEKLY,
                'oro.seo.system_configuration.fields.changefreq.choice.monthly.label'
                    => Configuration::CHANGEFREQ_MONTHLY,
                'oro.seo.system_configuration.fields.changefreq.choice.yearly.label'
                    => Configuration::CHANGEFREQ_YEARLY,
                'oro.seo.system_configuration.fields.changefreq.choice.never.label'
                    => Configuration::CHANGEFREQ_NEVER,
            ],
        ]);
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
        return self::NAME;
    }
}
