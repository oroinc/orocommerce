<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Controller;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\PromotionInformationLoadAppliedPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxPromotionControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            PromotionInformationLoadAppliedPromotionData::class
        ]);
    }

    public function testGetPromotionDataByPromotionAction()
    {
        /** @var Promotion $promotion */
        $promotion = $this->getReference(LoadPromotionData::ORDER_AMOUNT_PROMOTION);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_promotion_get_promotion_by_promotion',
                [
                    'id' => $promotion->getId()
                ]
            )
        );
        $result = $this->client->getResponse();

        static::assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);

        static::assertStringContainsString($promotion->getRule()->getName(), $jsonContent);
    }

    public function testGetPromotionDataByAppliedPromotionAction()
    {
        /** @var AppliedPromotion $appliedPromotion */
        $appliedPromotion = $this->getReference(PromotionInformationLoadAppliedPromotionData::APPLIED_PROMOTION_1);
        $promotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_promotion_get_promotion_by_applied_promotion',
                [
                    'id' => $appliedPromotion->getId()
                ]
            )
        );
        $result = $this->client->getResponse();

        static::assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);

        static::assertStringContainsString($promotion->getRule()->getName(), $jsonContent);
    }
}
