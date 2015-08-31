<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Utils\FormUtils;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceListAwareSelectType;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class FrontendOrderLineItemType extends AbstractOrderLineItemType
{
    const NAME = 'orob2b_order_line_item_frontend';

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

        $resolver->setDefault(
            'page_component_options',
            ['view' => 'orob2border/js/app/views/frontend-line-item-view']
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
                ProductPriceListAwareSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.product.entity_label',
                    'create_enabled' => false,
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

        /** @var OrderLineItem $item */
        $item = $form->getData();
        $view->vars['disallow_delete'] = $item && $item->isFromExternalSource();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
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
        if (!$item->getProduct() || !$item->getOrder()) {
            return;
        }

        /** @var ProductPriceRepository $repository */
        $repository = $this->registry
            ->getManagerForClass($this->priceClass)
            ->getRepository($this->priceClass);

        $priceList = $this->priceListRequestHandler->getPriceList();
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

        $form->remove('productUnit');
        $form->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            [
                'label' => 'orob2b.product.productunit.entity_label',
                'required' => true,
                'choices' => $choices
            ]
        );
    }
}
