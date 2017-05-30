<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class ApiLoginId implements OptionInterface
{
    const API_LOGIN_ID = 'api_login_id';

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(ApiLoginId::API_LOGIN_ID)
            ->addAllowedTypes(ApiLoginId::API_LOGIN_ID, 'string');
    }
}
