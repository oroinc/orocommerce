<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;

abstract class BaseMetaFormExtension extends AbstractTypeExtension
{
    /**
     * Return the name of the extend entity which will be used for determining field labels
     * @return string
     */
    abstract public function getMetaFieldLabelPrefix();

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'metaTitles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => $this->getMetaFieldLabelPrefix() . '.meta_titles.label',
                    'required' => false,
                    'type' => 'text',
                ]
            )
            ->add(
                'metaDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => $this->getMetaFieldLabelPrefix() . '.meta_descriptions.label',
                    'required' => false,
                    'type' => 'textarea',
                ]
            )
            ->add(
                'metaKeywords',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => $this->getMetaFieldLabelPrefix() . '.meta_keywords.label',
                    'required' => false,
                    'type' => 'textarea',
                ]
            );
    }
}
