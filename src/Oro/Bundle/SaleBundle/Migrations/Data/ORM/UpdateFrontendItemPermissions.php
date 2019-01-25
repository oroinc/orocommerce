<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractMassUpdateCustomerUserRolePermissions;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;

/**
 * Update RequestProduct and RequestProductItem default permissions for predefined roles.
 */
class UpdateFrontendItemPermissions extends AbstractMassUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getACLData(): array
    {
        return [
            'ROLE_FRONTEND_ADMINISTRATOR' => [
                'entity:' . QuoteProduct::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'],
                'entity:' . QuoteProductRequest::class => [
                    'VIEW_SYSTEM',
                    'CREATE_SYSTEM',
                    'EDIT_SYSTEM',
                    'DELETE_SYSTEM'
                ]
            ],
            'ROLE_FRONTEND_BUYER' => [
                'entity:' . QuoteProduct::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'],
                'entity:' . QuoteProductRequest::class => [
                    'VIEW_SYSTEM',
                    'CREATE_SYSTEM',
                    'EDIT_SYSTEM',
                    'DELETE_SYSTEM'
                ]
            ],
            'ROLE_FRONTEND_ANONYMOUS' => [
                'entity:' . QuoteProduct::class => ['VIEW_NONE', 'CREATE_NONE', 'EDIT_NONE', 'DELETE_NONE'],
                'entity:' . QuoteProductRequest::class => ['VIEW_NONE', 'CREATE_NONE', 'EDIT_NONE', 'DELETE_NONE']
            ]
        ];
    }
}
