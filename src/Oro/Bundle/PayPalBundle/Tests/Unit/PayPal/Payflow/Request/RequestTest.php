<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP\Encoder;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface;

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

        $resolver = new OptionsResolver();
        $request->configureOptions($resolver);
        $this->assertInternalType('array', $resolver->resolve($options));
    }

    /**
     * @return array
     */
    public function requestDataProvider()
    {
        $this->getFinder()
            ->files()
            ->in($this->getTestCasesDir())
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

    /**
     * @return string
     */
    protected function getTestCasesDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'requests';
    }
}
