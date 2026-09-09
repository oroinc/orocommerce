<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentTemplateData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ContentTemplateCloneAclTest extends WebTestCase
{
    use OperationAwareTestTrait;
    use RolePermissionExtension;

    private const OPERATION_NAME = 'oro_cms_content_template_clone';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadContentTemplateData::class]);
    }

    public function testCloneWhenViewIsGranted(): void
    {
        $countBefore = $this->getContentTemplateCount();

        $this->executeCloneOperation();

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        self::assertSame($countBefore + 1, $this->getContentTemplateCount());
    }

    public function testCloneWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            ContentTemplate::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );
        $countBefore = $this->getContentTemplateCount();

        $this->executeCloneOperation();

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertSame($countBefore, $this->getContentTemplateCount());
    }

    private function executeCloneOperation(): void
    {
        /** @var ContentTemplate $contentTemplate */
        $contentTemplate = $this->getReference(LoadContentTemplateData::CONTENT_TEMPLATE_2);
        $entityId = $contentTemplate->getId();
        $parameters = $this->getOperationExecuteParams(self::OPERATION_NAME, $entityId, ContentTemplate::class);
        $this->clearRequestStack();

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => self::OPERATION_NAME,
                    'route' => 'oro_cms_content_template_view',
                    'entityId' => $entityId,
                    'entityClass' => ContentTemplate::class
                ]
            ),
            $parameters,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    private function clearRequestStack(): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        while (null !== $requestStack->getMainRequest()) {
            $requestStack->pop();
        }
    }

    private function getContentTemplateCount(): int
    {
        return (int)$this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(ContentTemplate::class, 'e')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(ContentTemplate::class);
    }
}
