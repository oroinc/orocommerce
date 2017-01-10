<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OrderAddressType extends AbstractOrderAddressType
{
    const NAME = 'oro_order_address_type';

    /**
     * @param FormBuilderInterface $builder
     * @param string $type - address type
     * @param CustomerOwnerAwareInterface $entity
     * @param bool $isManualEditGranted
     * @param bool $isEditEnabled
     *
     * @return bool
     */
    protected function initCustomerAddressField(
        FormBuilderInterface $builder,
        $type,
        CustomerOwnerAwareInterface $entity,
        $isManualEditGranted,
        $isEditEnabled
    ) {
        if ($isEditEnabled) {
            $addresses = $this->orderAddressManager->getGroupedAddresses($entity, $type);

            $customerAddressOptions = [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'choices' => $this->getChoices($addresses),
                'configs' => ['placeholder' => 'oro.order.form.address.choose'],
                'attr' => [
                    'data-addresses' => json_encode($this->getPlainData($addresses)),
                    'data-default' => $this->getDefaultAddressKey($entity, $type, $addresses),
                ],
            ];

            if ($isManualEditGranted) {
                $customerAddressOptions['choices'] = array_merge(
                    $customerAddressOptions['choices'],
                    ['oro.order.form.address.manual']
                );
                $customerAddressOptions['configs']['placeholder'] = 'oro.order.form.address.choose_or_create';
            }

            $builder->add('customerAddress', 'genemu_jqueryselect2_choice', $customerAddressOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        foreach ($view->children as $child) {
            $child->vars['required'] = false;
            unset(
                $child->vars['attr']['data-validation'],
                $child->vars['attr']['data-required'],
                $child->vars['label_attr']['data-required']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_address';
    }

    /**
     * @return string
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
