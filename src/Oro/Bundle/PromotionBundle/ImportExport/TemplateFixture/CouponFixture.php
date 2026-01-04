<?php

namespace Oro\Bundle\PromotionBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

class CouponFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    public const COUPON_CODE = 'example-coupon';

    #[\Override]
    protected function createEntity($key)
    {
        return new Coupon();
    }

    #[\Override]
    public function getEntityClass()
    {
        return Coupon::class;
    }

    /**
     * @param Coupon $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity)
    {
        switch ($key) {
            case self::COUPON_CODE:
                $promotion = new Promotion();
                $owner = new BusinessUnit();
                $owner->setName('Main BU');

                $entity->setCode($key)
                    ->setPromotion($promotion)
                    ->setUsesPerCoupon(100)
                    ->setUsesPerPerson(3)
                    ->setValidFrom((new \DateTime()))
                    ->setValidUntil((new \DateTime())->modify('+1 year'))
                ;

                $entity->setOwner($owner);
                return;
        }

        parent::fillEntityData($key, $entity);
    }

    #[\Override]
    public function getData()
    {
        return $this->getEntityData(self::COUPON_CODE);
    }
}
