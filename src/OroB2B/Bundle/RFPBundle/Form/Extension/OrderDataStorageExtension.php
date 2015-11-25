<?php

namespace OroB2B\Bundle\RFPBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class OrderDataStorageExtension extends AbstractTypeExtension
{
    const OFFERS_DATA_KEY = 'offers';

    /** @var RequestStack */
    protected $requestStack;

    /** @var ProductDataStorage */
    protected $storage;

    /**
     * @param RequestStack $requestStack
     * @param ProductDataStorage $storage
     */
    public function __construct(RequestStack $requestStack, ProductDataStorage $storage)
    {
        $this->requestStack = $requestStack;
        $this->storage = $storage;
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->requestStack->getCurrentRequest()->get(ProductDataStorage::STORAGE_KEY)) {
            return;
        }

        $builder->add(
            'offers',
            'choice',
            [
                'mapped' => false,
                'multiple' => false,
                'expanded' => true,
                'choices' => []
            ]
        );
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        if (!$this->requestStack->getCurrentRequest()->get(ProductDataStorage::STORAGE_KEY)) {
            return;
        }

        $resolver->setNormalizer(
            'sections',
            function (Options $options, array $sections) {
                $sections['offers'] = ['data' => [], 'order' => 5];

                return $sections;
            }
        );
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return 'orob2b_order_line_item';
    }
}
