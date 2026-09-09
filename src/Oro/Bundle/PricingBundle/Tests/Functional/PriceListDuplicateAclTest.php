<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class PriceListDuplicateAclTest extends WebTestCase
{
    use OperationAwareTestTrait;
    use RolePermissionExtension;

    private const OPERATION_NAME = 'oro_pricing_price_list_duplicate';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadPriceLists::class]);
    }

    public function testDuplicateWhenViewIsGranted(): void
    {
        $this->executeDuplicateOperation();

        self::assertNotSame(
            Response::HTTP_FORBIDDEN,
            $this->client->getResponse()->getStatusCode(),
            'The duplicate operation must not be rejected when the source price list is readable'
        );
    }

    public function testDuplicateWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            PriceList::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );
        $countBefore = $this->getPriceListCount();

        $this->executeDuplicateOperation();

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertSame($countBefore, $this->getPriceListCount());
    }

    private function executeDuplicateOperation(): void
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $entityId = $priceList->getId();

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => self::OPERATION_NAME,
                    'route' => 'oro_pricing_price_list_view',
                    'entityId' => $entityId,
                    'entityClass' => PriceList::class
                ]
            ),
            $this->getOperationExecuteParams(self::OPERATION_NAME, $entityId, PriceList::class),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    private function getPriceListCount(): int
    {
        return (int)$this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(PriceList::class, 'e')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
    }
}
