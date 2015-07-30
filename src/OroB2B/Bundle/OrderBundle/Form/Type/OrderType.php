<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use OroB2B\Bundle\SaleBundle\Autocomplete\SearchHandler;

class OrderType extends AbstractType
{
    const NAME = 'orob2b_order_order';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('identifier', 'hidden')
            ->add('owner', 'oro_user_select', [
                'label'     => 'orob2b.order.owner.label',
                'required'  => true,
            ])
            ->add('accountUser', AccountUserSelectType::NAME, [
                'label'     => 'orob2b.order.account_user.label',
                'required'  => false,
                'configs'   => [
                    'component' => 'account-user-autocomplete',
                    'delimiter' => SearchHandler::DELIMITER,
                ],
            ])
            ->add('account', CustomerSelectType::NAME, [
                'label'     => 'orob2b.order.account.label',
                'required'  => false,
            ])
            ->add(
                'orderProducts',
                OrderProductCollectionType::NAME,
                [
                    'add_label' => 'orob2b.order.orderproduct.add_label',
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => $this->dataClass,
            'intention'     => 'order_order',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
