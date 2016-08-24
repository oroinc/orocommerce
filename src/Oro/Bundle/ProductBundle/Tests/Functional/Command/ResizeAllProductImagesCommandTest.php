<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Oro\Bundle\ProductBundle\Command\ResizeAllProductImagesCommand;
use Oro\Bundle\ProductBundle\Command\ResizeProductImageCommand;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ResizeAllProductImagesCommandTest extends WebTestCase
{
    /** @var Application */
    protected $application;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductImageData::class]);

        $kernel = self::getContainer()->get('kernel');
        $this->application = new Application($kernel);
        $this->application->add(new ResizeAllProductImagesCommand());
    }

    public function testResizeAllProductImages()
    {
        $command = $this->application->find(ResizeAllProductImagesCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $doctrine = self::getContainer()->get('doctrine');
        $jobRepository = $doctrine->getRepository(Job::class);
        $jobRepository->createQueryBuilder('job')->delete()->getQuery()->execute();

        $commandTester->execute(['command' => $command->getName()]);

        $jobs = $jobRepository->findBy(['command' => ResizeProductImageCommand::COMMAND_NAME]);

        self::assertEquals(2, count($jobs));
    }
}
