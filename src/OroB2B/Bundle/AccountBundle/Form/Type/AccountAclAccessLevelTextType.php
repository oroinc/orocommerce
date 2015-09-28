<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

use OroB2B\Bundle\AccountBundle\Acl\Resolver\RoleTranslationPrefixResolver;

class AccountAclAccessLevelTextType extends AbstractType
{
    const NAME = 'orob2b_account_acl_access_level_text';

    /**
     * @var RoleTranslationPrefixResolver
     */
    protected $roleTranslationPrefixResolver;

    /**
     * @param RoleTranslationPrefixResolver $roleTranslationPrefixResolver
     */
    public function __construct(RoleTranslationPrefixResolver $roleTranslationPrefixResolver)
    {
        $this->roleTranslationPrefixResolver = $roleTranslationPrefixResolver;
    }

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

        //uses on view page for rendering preloaded string (role permission name)
        $view->vars['translation_prefix'] = $this->roleTranslationPrefixResolver->getPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
