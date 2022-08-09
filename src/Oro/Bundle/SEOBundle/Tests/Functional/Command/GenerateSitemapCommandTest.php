<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CronBundle\Entity\Repository\ScheduleRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SEOBundle\Command\GenerateSitemapCommand;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Oro\Bundle\SEOBundle\EventListener\UpdateCronDefinitionConfigListener;
use Oro\Bundle\SEOBundle\Topic\GenerateSitemapTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GenerateSitemapCommandTest extends WebTestCase
{
    use MessageQueueExtension,
        ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testCommand(): void
    {
        self::runCommand(GenerateSitemapCommand::getDefaultName());

        $traces = self::getMessageCollector()->getTopicSentMessages(GenerateSitemapTopic::getName());

        self::assertCount(1, $traces);
        $data = array_shift($traces);
        self::assertEquals(['topic' => GenerateSitemapTopic::getName(), 'message' => []], $data);
    }

    public function testGetDefaultDefinitions(): void
    {
        /** @var ScheduleRepository $repo */
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
