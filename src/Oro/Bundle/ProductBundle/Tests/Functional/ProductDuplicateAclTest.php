<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ProductDuplicateAclTest extends WebTestCase
{
    use OperationAwareTestTrait;
    use RolePermissionExtension;

    private const OPERATION_NAME = 'oro_product_duplicate';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductData::class]);
    }

    public function testDuplicateWhenViewIsGranted(): void
    {
        $countBefore = $this->getProductCount();

        $this->executeDuplicateOperation();

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        self::assertSame($countBefore + 1, $this->getProductCount());
    }

    public function testDuplicateWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            Product::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );
        $countBefore = $this->getProductCount();

        $this->executeDuplicateOperation();

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertSame($countBefore, $this->getProductCount());
    }

    private function executeDuplicateOperation(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $entityId = $product->getId();

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => self::OPERATION_NAME,
                    'route' => 'oro_product_view',
                    'entityId' => $entityId,
                    'entityClass' => Product::class
                ]
            ),
            $this->getOperationExecuteParams(self::OPERATION_NAME, $entityId, Product::class),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    private function getProductCount(): int
    {
        return (int)$this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Product::class, 'e')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
    }
}
