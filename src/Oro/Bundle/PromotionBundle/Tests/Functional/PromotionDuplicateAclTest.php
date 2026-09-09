<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class PromotionDuplicateAclTest extends WebTestCase
{
    use OperationAwareTestTrait;
    use RolePermissionExtension;

    private const OPERATION_NAME = 'oro_promotion_duplicate';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadPromotionData::class]);
    }

    public function testDuplicateWhenViewIsGranted(): void
    {
        $this->executeDuplicateOperation();

        self::assertNotSame(
            Response::HTTP_FORBIDDEN,
            $this->client->getResponse()->getStatusCode(),
            'The duplicate operation must not be rejected when the source promotion is readable'
        );
    }

    public function testDuplicateWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            Promotion::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );
        $countBefore = $this->getPromotionCount();

        $this->executeDuplicateOperation();

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertSame($countBefore, $this->getPromotionCount());
    }

    private function executeDuplicateOperation(): void
    {
        /** @var Promotion $promotion */
        $promotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);
        $entityId = $promotion->getId();

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => self::OPERATION_NAME,
                    'route' => 'oro_promotion_view',
                    'entityId' => $entityId,
                    'entityClass' => Promotion::class
                ]
            ),
            $this->getOperationExecuteParams(self::OPERATION_NAME, $entityId, Promotion::class),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    private function getPromotionCount(): int
    {
        return (int)$this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Promotion::class, 'e')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Promotion::class);
    }
}
