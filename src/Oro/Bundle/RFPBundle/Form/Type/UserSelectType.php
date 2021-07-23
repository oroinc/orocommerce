<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RFPBundle\Form\DataTransformer\UserIdToEmailTransformer;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType as BaseUserSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectType extends AbstractType
{
    const NAME = 'oro_rfp_user_select';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'transformer' => new UserIdToEmailTransformer($this->registry)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseUserSelectType::class;
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
        return static::NAME;
    }
}
