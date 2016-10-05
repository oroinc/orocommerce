<?php

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ProductBundle\Storage\DataStorageInterface;
use Oro\Bundle\RFPBundle\Form\Type\OffersType;
use Oro\Bundle\RFPBundle\Storage\OffersFormStorage;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderLineItemDataStorageExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const OFFERS_DATA_KEY = 'offers';

    /** @var RequestStack */
    protected $requestStack;

    /** @var DataStorageInterface */
    protected $sessionStorage;

    /** @var \Oro\Bundle\OrderBundle\Form\Section\SectionProvider */
    protected $sectionProvider;

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

    /**
     * @param \Oro\Bundle\OrderBundle\Form\Section\SectionProvider $sectionProvider
     */
    public function setSectionProvider($sectionProvider)
    {
        $className = 'Oro\Bundle\OrderBundle\Form\Section\SectionProvider';

        if (!is_a($sectionProvider, $className)) {
            $actual = is_object($this->sectionProvider) ?
                get_class($this->sectionProvider) : gettype($this->sectionProvider);

            throw new \InvalidArgumentException(sprintf('"%s" expected, "%s" given', $className, $actual));
        }

        $this->sectionProvider = $sectionProvider;
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
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($this->sectionProvider && $this->isApplicable()) {
            $this->sectionProvider->addSections(
                $this->getExtendedType(),
                [
                    self::OFFERS_DATA_KEY => [
                        'data' => [
                            self::OFFERS_DATA_KEY => [],
                            OffersFormStorage::DATA_KEY => [],
                        ],
                        'order' => 5,
                    ],
                ]
            );
        }
    }

    /**
     * @return bool
     */
    protected function isApplicable()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->isFeaturesEnabled() && $request && $request->get(DataStorageInterface::STORAGE_KEY);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return 'oro_order_line_item';
    }
}
