<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class FrontendOrderLineItemType extends AbstractOrderLineItemType
{
    const NAME = 'oro_order_line_item_frontend';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var string
     */
    protected $priceClass;

    /**
     * @param ManagerRegistry $registry
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param string $priceClass
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListRequestHandler $priceListRequestHandler,
        $priceClass
    ) {
        $this->registry = $registry;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->priceClass = $priceClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'page_component' => '',
                'page_component_options' => []
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.product.entity_label',
                    'create_enabled' => false,
                    'data_parameters' => [
                        'scope' => 'order',
                        'price_list' => 'default_account_user'
                    ]
                ]
            )
            ->addEventListener(
                FormEvents::POST_SET_DATA,
                function (FormEvent $event) {
                    $form = $event->getForm();
                    /** @var OrderLineItem $item */
                    $item = $form->getData();
                    if ($item && $item->isFromExternalSource()) {
                        $this->disableFieldChanges($form, 'product');
                        $this->disableFieldChanges($form, 'productUnit');
                        $this->disableFieldChanges($form, 'quantity');
                        $this->disableFieldChanges($form, 'shipBy');
                    }
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $this->getSectionProvider()->addSections(
            $this->getName(),
            ['price' => ['data' => ['price' => []], 'order' => 20]]
        );

        /** @var OrderLineItem $item */
        $item = $form->getData();
        $view->vars['disallow_delete'] = $item && $item->isFromExternalSource();
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

    /**
     * @param FormInterface $form
     * @param string $childName
     */
    protected function disableFieldChanges(FormInterface $form, $childName)
    {
        FormUtils::replaceField($form, $childName, ['disabled' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAvailableUnits(FormInterface $form)
    {
        /** @var OrderLineItem $item */
        $item = $form->getData();
        if (!$item->getOrder()) {
            return;
        }

        $choices = [$item->getProductUnit()];
        if ($item->getProduct()) {
            $choices = $this->getProductAvailableChoices($item);
        }

        $form->remove('productUnit');
        $form->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            [
                'label' => 'oro.product.productunit.entity_label',
                'required' => true,
                'choices' => $choices
            ]
        );
    }

    /**
     * @param OrderLineItem $item
     * @return array|ProductUnit[]
     */
    protected function getProductAvailableChoices(OrderLineItem $item)
    {
        /** @var ProductPriceRepository $repository */
        $repository = $this->registry
            ->getManagerForClass($this->priceClass)
            ->getRepository($this->priceClass);

        $priceList = $this->priceListRequestHandler->getPriceListByAccount();
        $choices = $repository->getProductUnitsByPriceList(
            $priceList,
            $item->getProduct(),
            $item->getOrder()->getCurrency()
        );

        $hasChoice = false;
        foreach ($choices as $unit) {
            if ($unit->getCode() === $item->getProductUnit()->getCode()) {
                $hasChoice = true;
                break;
            }
        }
        if (!$hasChoice) {
            $choices[] = $item->getProductUnit();
        }

        return $choices;
    }
}
