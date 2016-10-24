<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContentNodeType extends AbstractType
{
    const NAME = 'oro_web_catalog_content_node';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'parentNode',
                EntityIdentifierType::NAME,
                ['class' => ContentNode::class, 'multiple' => false]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'label'    => 'oro.webcatalog.contentnode.name.label',
                    'required' => true,
                ]
            )
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label'    => 'oro.webcatalog.contentnode.titles.label',
                    'required' => true,
                    'options'  => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'slugs',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label'    => 'oro.webcatalog.contentnode.slugs.label',
                    'required' => true,
                    'options'  => ['constraints' => [new NotBlank()]],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ContentNode::class,
            ]
        );
    }

    /**
     * @return string
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
