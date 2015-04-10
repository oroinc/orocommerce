<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Functional\Twig;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ApplicationUrlExtensionTest extends WebTestCase
{
    public function testGetApplicationUrl()
    {
        $this->initClient();

        // template contains invocation of application_url twig function
        $template = __DIR__ . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'application_url.html.twig';

        $twig = $this->getContainer()->get('twig');
        $result = $twig->render(
            $template,
            ['name' => 'oro_default', 'parameters' => ['application' => 'admin', 'qwe' => 'rty']]
        );
        $result = trim($result);

        $this->assertStringEndsWith('/admin.php/?qwe=rty', $result);
    }
}
