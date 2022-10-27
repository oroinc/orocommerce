<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapTopic;
use Oro\Bundle\SEOBundle\Command\GenerateSitemapCommand;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Oro\Bundle\SEOBundle\EventListener\UpdateCronDefinitionConfigListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GenerateSitemapCommandTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testCommand(): void
    {
        self::runCommand(GenerateSitemapCommand::getDefaultName());

        self::assertMessageSent(GenerateSitemapTopic::getName(), []);
    }

    public function testGetDefaultDefinitions(): void
    {
        /** @var EntityRepository $repo */
        $repo = self::getContainer()->get('doctrine')->getRepository(Schedule::class);
        /** @var Schedule $commandSchedule */
        $commandSchedule = $repo->findOneBy(['command' => GenerateSitemapCommand::getDefaultName()]);
        self::assertNotEmpty($commandSchedule);
        self::assertSame(Configuration::DEFAULT_CRON_DEFINITION, $commandSchedule->getDefinition());

        $configManager = self::getConfigManager();
        $configManager->set(UpdateCronDefinitionConfigListener::CONFIG_FIELD, '0 0 0 0 *');
        $configManager->flush();
        self::runCommand('oro:cron:definitions:load', []);

        $commandSchedule = $repo->findOneBy(['command' => GenerateSitemapCommand::getDefaultName()]);
        self::assertSame('0 0 0 0 *', $commandSchedule->getDefinition());
    }
}
