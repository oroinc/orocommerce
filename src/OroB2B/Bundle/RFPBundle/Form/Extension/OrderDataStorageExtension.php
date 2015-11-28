<?php

namespace OroB2B\Bundle\RFPBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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

    /** @var array */
    protected $offers = [];

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

        $data = $this->storage->get();
        if (array_key_exists(self::OFFERS_DATA_KEY, $data)) {
            $this->offers = $data[self::OFFERS_DATA_KEY];
        }

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $event->getForm()->add(self::OFFERS_DATA_KEY, 'choice', ['mapped' => false, 'expanded' => true]);
            }
        );
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->requestStack->getCurrentRequest()->get(ProductDataStorage::STORAGE_KEY)) {
            return;
        }

        $view->offsetGet(self::OFFERS_DATA_KEY)->vars['offers'] = $this->getOffers($form);
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    protected function getOffers(FormInterface $form)
    {
        if (!$form->getParent()) {
            return [];
        }

        $lineItem = $form->getData();

        $collection = $form->getParent()->getData();
        if (!$collection instanceof Collection) {
            return [];
        }

        $key = $collection->indexOf($lineItem);

        if (!array_key_exists($key, $this->offers)) {
            return [];
        }

        return (array)$this->offers[$key];
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
                $sections[self::OFFERS_DATA_KEY] = ['data' => [self::OFFERS_DATA_KEY => []], 'order' => 5];

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
