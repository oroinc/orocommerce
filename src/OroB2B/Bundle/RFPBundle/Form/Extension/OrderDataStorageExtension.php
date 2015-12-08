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

    /**
     * @var array|bool false if not initialized
     */
    private $offers = false;

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
        if (!$this->isApplicable()) {
            return;
        }

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $form->add(
                    self::OFFERS_DATA_KEY,
                    'choice',
                    [
                        'mapped' => false,
                        'expanded' => true,
                        'choices' => array_keys($this->getOffers($form)),
                        'choices_as_values' => true,
                    ]
                );
            }
        );
    }

    /**
     * @return array
     */
    protected function getOffersStorageData()
    {
        if (false !== $this->offers) {
            return $this->offers;
        }
        $this->offers = [];
        $data = (array)$this->storage->get();
        if (array_key_exists(self::OFFERS_DATA_KEY, $data) && is_array($data[self::OFFERS_DATA_KEY])) {
            $this->offers = $data[self::OFFERS_DATA_KEY];
        }

        return (array)$this->offers;
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->isApplicable()) {
            return;
        }

        if (!$view->offsetExists(self::OFFERS_DATA_KEY)) {
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
        $lineItem = $form->getData();
        if (!$lineItem) {
            return [];
        }

        $parent = $form->getParent();
        if (!$parent) {
            return [];
        }

        $collection = $parent->getData();
        if (!$collection instanceof Collection) {
            return [];
        }

        $key = $collection->indexOf($lineItem);
        if (false === $key) {
            return [];
        }

        $offers = $this->getOffersStorageData();
        if (!array_key_exists($key, $offers)) {
            return [];
        }

        return (array)$offers[$key];
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        if (!$this->isApplicable() || !$this->getOffersStorageData()) {
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

    /**
     * @return bool
     */
    protected function isApplicable()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request && $request->get(ProductDataStorage::STORAGE_KEY);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return 'orob2b_order_line_item';
    }
}
