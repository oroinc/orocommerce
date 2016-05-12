<?php

namespace OroB2B\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;

class ProductFormExtension extends AbstractTypeExtension
{
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'metaTitles',
            LocalizedFallbackValueCollectionType::NAME,
            [
                'label' => 'orob2b.seo.meta-title.label',
                'required' => false,
            ]
        )
            ->add(
            'metaDescriptions',
            LocalizedFallbackValueCollectionType::NAME,
            [
                'label' => 'orob2b.seo.meta-description.label',
                'required' => false,
                'field' => 'text',
                'type' => OroRichTextType::NAME,
                'options' => [
                    'wysiwyg_options' => [
                        'statusbar' => true,
                        'resize' => true,
                        'width' => 500,
                        'height' => 300,
                        'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
                        'toolbar' =>
                            [reset(OroRichTextType::$toolbars[OroRichTextType::TOOLBAR_DEFAULT]) . ' | fullscreen'],
                    ],
                ],
            ]
        )
            ->add(
            'metaKeywords',
            LocalizedFallbackValueCollectionType::NAME,
            [
                'label' => 'orob2b.seo.meta-keywords.label',
                'required' => false,
                'field' => 'text',
                'type' => OroRichTextType::NAME,
                'options' => [
                    'wysiwyg_options' => [
                        'statusbar' => true,
                        'resize' => true,
                        'width' => 500,
                        'height' => 300,
                        'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
                        'toolbar' =>
                            [reset(OroRichTextType::$toolbars[OroRichTextType::TOOLBAR_DEFAULT]) . ' | fullscreen'],
                    ],
                ],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        //TODO : add handling of meta title,description,keywords and save in FallbackLocaleValue
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }
}
