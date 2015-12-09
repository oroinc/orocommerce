<?php

namespace OroB2B\Bundle\RFPBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\RFPBundle\Form\Type\OffersType;
use OroB2B\Bundle\RFPBundle\Storage\OffersFormStorage;
use OroB2B\Bundle\ProductBundle\Storage\DataStorageInterface;

class OrderDataStorageExtension extends AbstractTypeExtension
{
    const OFFERS_DATA_KEY = 'offers';

    /** @var RequestStack */
    protected $requestStack;

    /** @var DataStorageInterface */
    protected $sessionStorage;

    /**
     * @var array|bool false if not initialized
     */
    private $offers = false;

    /** @var OffersFormStorage */
    private $formStorage;

    /**
     * @param RequestStack $requestStack
     * @param DataStorageInterface $sessionStorage
     * @param OffersFormStorage $formStorage
     */
    public function __construct(
        RequestStack $requestStack,
        DataStorageInterface $sessionStorage,
        OffersFormStorage $formStorage
    ) {
        $this->requestStack = $requestStack;
        $this->sessionStorage = $sessionStorage;
        $this->formStorage = $formStorage;
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
                $offers = $this->getOffers($form);
                $form->add(
                    OffersFormStorage::DATA_KEY,
                    'hidden',
                    ['data' => $this->formStorage->getRawData($offers), 'mapped' => false]
                );
                $form->add(self::OFFERS_DATA_KEY, OffersType::NAME, [OffersType::OFFERS_OPTION => $offers]);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $event->getForm()->add(
                    self::OFFERS_DATA_KEY,
                    OffersType::NAME,
                    [OffersType::OFFERS_OPTION => $this->formStorage->getData($event->getData())]
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

        $this->offers = $this->sessionStorage->get();
        $this->sessionStorage->remove();

        return $this->offers;
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
        if (!$this->isApplicable()) {
            return;
        }

        $resolver->setNormalizer(
            'sections',
            function (Options $options, array $sections) {
                $sections[self::OFFERS_DATA_KEY] = [
                    'data' => [
                        self::OFFERS_DATA_KEY => [],
                        OffersFormStorage::DATA_KEY => [],
                    ],
                    'order' => 5,
                ];

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

        return $request && $request->get(DataStorageInterface::STORAGE_KEY);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return 'orob2b_order_line_item';
    }
}
