<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Request;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\Encoder;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestInterface;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /** @var Finder */
    protected $finder;

    /**
     * @param string $requestClass
     * @param string $requestString
     * @param string|array $error
     * @dataProvider requestDataProvider
     */
    public function testRequest($requestClass, $requestString, $error = [])
    {
        if ($error) {
            list ($exception, $message) = $error;
            $this->setExpectedException($exception, $message);
        }

        // new lines in yml
        $requestString = str_replace("\n", '', $requestString);
        $options = (new Encoder())->decode($requestString);
        /** @var RequestInterface $request */
        $request = new $requestClass($options);
        $this->assertInternalType('array', $request->getOptions());
    }

    /**
     * @return array
     */
    public function requestDataProvider()
    {
        $this->getFinder()
            ->files()
            ->in(__DIR__)
            ->name('*.yml');

        $cases = [];

        /** @var SplFileInfo $file */
        foreach ($this->getFinder() as $file) {
            foreach (Yaml::parse($file->getContents()) as $testCaseName => $testCase) {
                $cases[$file->getFilename() . ' ' . $testCaseName] = $testCase;
            }
        }

        return $cases;
    }

    /**
     * @return Finder
     */
    protected function getFinder()
    {
        if (!$this->finder) {
            $this->finder = new Finder();
        }

        return $this->finder;
    }
}
