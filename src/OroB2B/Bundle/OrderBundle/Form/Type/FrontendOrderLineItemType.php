<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceMatchingProvider;

class FrontendOrderLineItemType extends AbstractOrderLineItemType
{
    const NAME = 'orob2b_order_line_item_frontend';

    /**
     * @var ProductPriceMatchingProvider
     */
    protected $provider;

    /**
     * @param ProductPriceMatchingProvider $provider
     */
    public function __construct(ProductPriceMatchingProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var OrderLineItem $item */
                $item = $form->getData();
                if ($item && $item->isFromExternalSource()) {
                    $this->disableFieldChanges($form, 'product');
                    $this->disableFieldChanges($form, 'productUnit');
                    $this->disableFieldChanges($form, 'quantity');
                }
            }
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($options) {
                /** @var OrderLineItem $item */
                $item = $event->getData();
                if ($item && $item->getProduct() && $item->getProductUnit()) {
                    $price = $this->provider->matchPrice(
                        $item->getProduct(),
                        $item->getProductUnit(),
                        $item->getQuantity(),
                        $options['currency']
                    );

                    $item->setPrice($price);
                }
            }
        );
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
}
