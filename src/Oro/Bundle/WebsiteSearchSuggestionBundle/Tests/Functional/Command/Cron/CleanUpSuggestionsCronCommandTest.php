<?php

namespace Functional\Command\Cron;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\DataFixtures\LoadProductSuggestionsData;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\WebsiteSearchSuggestionsFeatureTrait;

class CleanUpSuggestionsCronCommandTest extends WebTestCase
{
    use MessageQueueExtension;
    use WebsiteSearchSuggestionsFeatureTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductSuggestionsData::class
        ]);
        $this->enableFeature();
    }

    public function testThatSuggestionsAreDeleted(): void
    {
        $repository = $this->getDataFixturesExecutorEntityManager()->getRepository(Suggestion::class);

        $commandOutput = self::runCommand('oro:cron:website-search-suggestions:clean-up');
        self::assertGreaterThan(0, $repository->count([]));
        $this->consumeAllMessages();

        self::assertEquals('[INFO] Initiated the clean up of outdated website search suggestions.', $commandOutput);
        self::assertEquals(1, $repository->count([]));
    }
}
