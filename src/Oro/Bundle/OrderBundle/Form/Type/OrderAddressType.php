<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountOwnerAwareInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OrderAddressType extends AbstractOrderAddressType
{
    const NAME = 'oro_order_address_type';

    /**
     * @param FormBuilderInterface $builder
     * @param string $type - address type
     * @param AccountOwnerAwareInterface $entity
     * @param bool $isManualEditGranted
     * @param bool $isEditEnabled
     *
     * @return bool
     */
    protected function initAccountAddressField(
        FormBuilderInterface $builder,
        $type,
        AccountOwnerAwareInterface $entity,
        $isManualEditGranted,
        $isEditEnabled
    ) {
        if ($isEditEnabled) {
            $addresses = $this->orderAddressManager->getGroupedAddresses($entity, $type);

            $accountAddressOptions = [
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
                $accountAddressOptions['choices'] = array_merge(
                    $accountAddressOptions['choices'],
                    ['oro.order.form.address.manual']
                );
                $accountAddressOptions['configs']['placeholder'] = 'oro.order.form.address.choose_or_create';
            }

            $builder->add('accountAddress', 'genemu_jqueryselect2_choice', $accountAddressOptions);
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
