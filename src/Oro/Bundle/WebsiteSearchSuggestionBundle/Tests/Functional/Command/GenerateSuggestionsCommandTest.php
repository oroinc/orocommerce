<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\DataFixtures\LoadProductSuggestionsData;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\WebsiteSearchSuggestionsFeatureTrait;

final class GenerateSuggestionsCommandTest extends WebTestCase
{
    use MessageQueueExtension;
    use WebsiteSearchSuggestionsFeatureTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductSuggestionsData::class,
        ]);
        $this->enableFeature();
    }

    public function testThatSuggestionsGenerated(): void
    {
        $repository = $this->getDataFixturesExecutorEntityManager()->getRepository(Suggestion::class);
        $amountSuggestionsBeforeGeneration = $repository->count([]);

        $commandOutput = self::runCommand('oro:website-search-suggestions:generate');
        $this->consumeAllMessages();

        self::assertEquals(
            '[INFO] Initiated the generation of website search suggestions for all products.',
            $commandOutput
        );
        self::assertGreaterThan($amountSuggestionsBeforeGeneration, $repository->count([]));
    }
}
