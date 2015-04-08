<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class RequestStatusChangeType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_status_change';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'status',
                RequestStatusSelectType::NAME,
                [
                    'label'       => 'orob2b.rfp.request.status.label',
                    'required'    => true,
                    'empty_value' => '',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'note',
                'oro_rich_text',
                [
                    'label'    => 'oro.note.entity_label',
                    'required' => false,
                ]
            );
    }
}
