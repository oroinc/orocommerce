<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\AccountBundle\Form\DataTransformer\UserIdToEntityTransformer;

class UserSelectType extends AbstractType
{
    const NAME = 'orob2b_account_user_select';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $userClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $userClass
     */
    public function __construct(ManagerRegistry $registry, $userClass)
    {
        $this->registry = $registry;
        $this->userClass = $userClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'transformer' => new UserIdToEntityTransformer($this->registry, $this->userClass)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_user_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
