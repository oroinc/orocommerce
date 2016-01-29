<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountUserCollectionType extends AbstractType
{
    const NAME = 'orob2b_account_account_user_collection';

    /** @var string */
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
    public function getParent()
    {
        return 'genemu_jqueryselect2_entity';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $items = [];
        foreach ($view->vars['choices'] as $choiceView) {
            /* @var $choiceView ChoiceView */
            $accountId = $choiceView->data->getAccount()->getId();

            $items[$choiceView->value] = [
                'value' => $choiceView->value,
                'label' => $choiceView->label,
                'account' => $accountId,
            ];
        }

        $view->vars['attr']['data-items'] = json_encode($items);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => $this->dataClass,
                'property' => 'fullName',
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'class' => 'account-accountuser-collection',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
