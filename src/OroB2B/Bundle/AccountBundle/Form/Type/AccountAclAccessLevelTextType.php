<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

class AccountAclAccessLevelTextType extends AbstractType
{
    const NAME = 'orob2b_account_acl_access_level_text';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $parent = $form->getParent()->getParent()->getParent();
        $parentData = $parent->getData();
        if ($parentData instanceof AclPrivilege) {
            $view->vars['identity'] = $parentData->getIdentity()->getId();
            $view->vars['level_label'] = AccessLevel::getAccessLevelName($form->getData());
        }

        $view->vars['translation_prefix'] = 'orob2b.account.security.access-level.';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
