<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Performance\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\SaleBundle\Tests\Performance\PerformanceMeasureTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

class DisableQuotesProcessTest extends WebTestCase
{
    use PerformanceMeasureTrait;

    const PROCESS_TRIGGER_NAME = 'expire_quotes';

    const MAX_EXECUTION_TIME = 10;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testDisableQuotesProcessPerformance()
    {
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $quotesRepo = $em->getRepository('OroB2BSaleBundle:Quote');

        $totalQuotes = $quotesRepo->countQuotes();
        if (0 == $totalQuotes) {
            $this->loadFixtures(['OroB2B\Bundle\SaleBundle\Tests\Performance\DataFixtures\LoadQuoteDataForPerformanceTest']);
            // Get new quote number after fixtures
            $totalQuotes = $quotesRepo->countQuotes();
        }
        $quotesToExpire = $quotesRepo->countQuotes(true);

        $expireQuotesTrigger = $em->getRepository('OroWorkflowBundle:ProcessTrigger')->findOneBy([
            'definition' => static::PROCESS_TRIGGER_NAME
        ]);
        $app = new Application($this->client->getKernel());
        $app->setAutoExit(false);
        $fp = tmpfile();
        $input = new StringInput(sprintf(
            'oro:process:handle-trigger --name=%s --id=%s',
            self::PROCESS_TRIGGER_NAME,
            $expireQuotesTrigger->getId()
        ));
        $output = new StreamOutput($fp);

        // measure trigger process performance
        self::startMeasurement(__METHOD__);
        $app->run($input, $output);
        // get duration in seconds
        $duration = self::stopMeasurement(__METHOD__) / 1000;

        // get output from the process
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output .= fread($fp, 4096);
        }
        fclose($fp);

        $this->assertLessThan(self::MAX_EXECUTION_TIME, $duration);

        fwrite(STDERR, print_r("\n", true));
        fwrite(STDERR, print_r("Total number of quotes in DB: $totalQuotes\n", true));
        fwrite(STDERR, print_r("Total number of quotes to mark as expired: $quotesToExpire. Message of process:\n\n", true));
        fwrite(STDERR, print_r($output, true));
        fwrite(STDERR, print_r("\n", true));
    }
}
