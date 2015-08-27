<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Utils\FormUtils;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;

class FrontendOrderLineItemType extends AbstractOrderLineItemType
{
    const NAME = 'orob2b_order_line_item_frontend';

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
}
