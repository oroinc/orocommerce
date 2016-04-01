<?php

namespace OroB2B\Bundle\PaymentBundle\Command;

use Guzzle\Http\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class PaypalCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {

        $client = new Client();

        $url = 'https://developer.paypal.com/docs/classic/payflow/integration-guide/';

        $res = $client->get($url)->send();
        $html = $res->getBody(true);
        $page = new Crawler();
        $page->addHtmlContent($html);
        $table = $page->filterXPath('html/body/main/div[1]/article//table[41]');
        $trs = $table->filter('tr');

        $codes = [];
        $consts = [];

        /** @var \DOMElement $node */
        foreach ($trs as $node) {
            $tds = (new Crawler($node))->filter('td');
            if ($tds->count() === 0) {
                continue;
            }
            $code = (int)$tds->first()->text();
            $lastTd = $tds->last();

            $message = $lastTd->filter('strong')->text();
            $constLength = strpos($message, '.') ?: strlen($message);
            $const = str_replace(
                [
                    ' ', '-',
                    'FRAUD_PROTECTION_SERVICES_FILTER_—',
                    'BUYER_AUTHENTICATION_SERVICE_—',
                    'VALIDATE_AUTHENTICATION_FAILED:',
                    ',', '(', ')'
                ],
                ['_', '_', 'FPSF', 'BAS', 'VAF'],
                strtoupper(substr($message, 0, $constLength))
            );

            $constCall = 'self::' . $const;
            if (array_key_exists($constCall, $codes)) {
                $const .= "_$code";
                $constCall = 'self::' . $const;
            }
            $codes[$constCall] = $message;
            $consts[] = "const $const = $code;";
        }
        $result = implode(PHP_EOL, $consts);

        $result .= PHP_EOL . PHP_EOL . 'protected static $messages = [' . PHP_EOL;

        foreach ($codes as $key => $value) {
            $result .= $key . ' => ' . "'$value'," . PHP_EOL;
        }

        $result .= '];';
        echo $result;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('payment:paypal')
            ->setDescription('Fetch paypal status codes.');
//            ->addOption(
//                'cache-dir',
//                null,
//                InputOption::VALUE_REQUIRED,
//                'The cache directory'
//            );
    }
}
