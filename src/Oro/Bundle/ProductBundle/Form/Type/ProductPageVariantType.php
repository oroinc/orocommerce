<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPageVariantType extends AbstractType
{
    const NAME = 'oro_product_page_variant';

    /**
     * @var ManagerRegistry
     */
    private $registry;

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
                'productPageProduct',
                ProductSelectType::NAME,
                [
                    'label' => 'oro.product.entity_label',
                    'required' => true
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::NAME,
                [
                    'label' => 'oro.webcatalog.contentvariant.scopes.label',
                    'required' => false,
                    'entry_options' => [
                        'scope_type' => 'web_content'
                    ]
                ]
            )
            ->add(
                'type',
                HiddenType::class
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof ContentVariantInterface) {
                    $data->setType(ProductPageContentVariantType::TYPE);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $metadata = $this->registry->getManager()->getClassMetadata(ContentVariantInterface::class);

        $resolver->setDefaults(
            [
                'data_class' => $metadata->getName()
            ]
        );
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
