<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Entity\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\RFPBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\RFPBundle\Entity\Repository\RequestRepository;
use Oro\Bundle\RFPBundle\Entity\Request;

class EmailOwnerProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailOwnerProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new EmailOwnerProvider();
    }

    public function testGetEmailOwnerClass()
    {
        $this->assertEquals(Request::class, $this->provider->getEmailOwnerClass());
    }

    public function testFindEmailOwner()
    {
        $request = new Request();
        $email = 'test@test.com';

        $repo = $this->createMock(RequestRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($request);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Request::class)
            ->willReturn($repo);

        $this->assertSame($request, $this->provider->findEmailOwner($em, $email));
    }
}
