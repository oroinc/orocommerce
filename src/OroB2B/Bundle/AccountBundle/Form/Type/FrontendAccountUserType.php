<?php
namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class FrontendAccountUserType extends AbstractType
{
    const NAME = 'orob2b_account_frontend_account_user';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return AccountUserType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

