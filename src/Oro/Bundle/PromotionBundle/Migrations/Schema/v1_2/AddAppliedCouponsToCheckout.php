<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddAppliedCouponsToCheckout implements Migration, ExtendExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addAppliedCouponsToCheckout($schema);
    }

    protected function addAppliedCouponsToCheckout(Schema $schema)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_promotion_applied_coupon',
            'checkout',
            'oro_checkout',
            'id',
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE',
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false]
            ]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            'oro_promotion_applied_coupon',
            'checkout',
            'oro_checkout',
            'appliedCoupons',
            ['coupon_code'],
            ['coupon_code'],
            ['coupon_code'],
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE'
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false]
            ]
        );
    }
}
