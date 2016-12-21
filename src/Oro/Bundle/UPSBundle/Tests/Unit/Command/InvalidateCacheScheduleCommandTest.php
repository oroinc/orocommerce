<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Command\Stub\TestOutput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InvalidateCacheScheduleCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InvalidateCacheScheduleCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    private $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UPSTransport
     */
    private $transport;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Input
     */
    private $input;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    private $repo;

    /**
     * @var TestOutput
     */
    private $output;

    protected function setUp()
    {
        $this->repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $em->expects(static::any())
            ->method('getRepository')
            ->with(UPSTransport::class)
            ->willReturn($this->repo);

        $this->managerRegistry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerRegistry->expects(static::any())
            ->method('getManagerForClass')
            ->with(UPSTransport::class)
            ->willReturn($em);

        $this->container = $this->createMock(ContainerBuilder::class);

        $this->transport = $this->getMockBuilder(UPSTransport::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new InvalidateCacheScheduleCommand();
        $this->command->setContainer($this->container);

        $this->input = $this->getMockForAbstractClass(InputInterface::class);
        $this->output = new TestOutput();
    }

    public function testConfigure()
    {
        $this->command->configure();

        static::assertNotEmpty($this->command->getDescription());
        static::assertNotEmpty($this->command->getName());
    }

    /**
     * @param int $id
     * @param \DateTime $invalidateAt
     * @param array $expectedOutput
     * @dataProvider executeProvider
     */
    public function testExecute($id, \DateTime $invalidateAt, $expectedOutput)
    {
        $upsCache = $this->getMockBuilder(UPSShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects(static::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['doctrine', 1, $this->managerRegistry],
                ['oro_ups.shipping_price_cache', 1, $upsCache],
                ['oro_shipping.shipping_price.provider.cache', 1, $shippingCache],
            ]);
        $this->transport->expects(static::exactly(2))
            ->method('getInvalidateCacheAt')
            ->willReturn($invalidateAt);

        $this->input->expects(static::exactly(1))
            ->method('getOption')
            ->with('id')
            ->willReturn($id);

        $this->repo->expects(static::once())
            ->method('find')
            ->with($id)
            ->willReturn($this->transport);

        $this->command->execute($this->input, $this->output);

        $messages = static::getObjectAttribute($this->output, 'messages');

        $found = 0;
        foreach ($messages as $message) {
            foreach ($expectedOutput as $expected) {
                if (strpos($message, $expected) !== false) {
                    $found++;
                }
            }
        }

        static::assertCount($found, $expectedOutput);
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return [
            'valid Year' => [
                'id' => 1,
                'invalidateAt' => new \DateTime(date('Y') . '-01-01T15:03:01.012345Z'),
                'output' => [
                    'Shipping Cache was successfully cleared',
                ],
            ],
            'wrong Year' => [
                'id' => 2,
                'invalidateAt' => new \DateTime('2001-01-01T15:03:01.012345Z'),
                'output' => [
                    'Shipping Cache was not cleared'
                ],
            ],
        ];
    }

    public function testExecuteNoIdSpecified()
    {
        $this->input->expects(static::once())
            ->method('getOption')
            ->with('id')
            ->willReturn(null);

        $this->command->execute($this->input, $this->output);

        static::assertAttributeEquals(['No UPS Transport identifier defined'], 'messages', $this->output);
    }
}
