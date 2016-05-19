<?php

namespace OroB2B\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

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
        $builder
            ->add(
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
        /* @var $product Product */
        $product = $event->getData();
        $entityManager = $this->registry->getManagerForClass('OroB2BFallbackBundle:LocalizedFallbackValue');

        $this->persistMetaFields($entityManager, $product->getMetaTitles());
        $this->persistMetaFields($entityManager, $product->getMetaDescriptions());
        $this->persistMetaFields($entityManager, $product->getMetaKeywords());
    }

    /**
     * Loop through list of LocalizedFallbackValue objects for a meta information field
     *
     * @param OroEntityManager $entityManager
     * @param LocalizedFallbackValue[] $metaFields
     */
    private function persistMetaFields($entityManager, $metaFields)
    {
        foreach ($metaFields as $field) {
            $entityManager->persist($field);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }
}
