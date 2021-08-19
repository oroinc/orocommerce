<?php

namespace Oro\Bundle\RFPBundle\Tests\Behat\Context;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Behat\Element\RequestForQuote;
use Oro\Bundle\RFPBundle\Tests\Behat\Page\RequestViewPage;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Mapping of RFQ view pages by application name
     * @var array
     */
    private $RFQViewPages = [
        'frontend' => 'RequestViewFrontendPage',
        'backend' => 'RequestViewBackendPage'
    ];

    /**
     * User friendly variable names => values context mapping
     * @var array
     */
    private $variablesMapping = [];

    /**
     * Example: I should see that Request status is "StatusName"
     *
     * @Then /^I should see that Request status is "(?P<status>[^"]*)"$/
     * @Then /^I should see RFQ status is "(?P<status>[^"]*)"$/
     * @param string $status
     */
    public function iShouldSeeOnFrontendRequestPageStatus($status)
    {
        /** @var RequestForQuote $pageElement */
        $pageElement = $this->createElement('RequestForQuote');
        $pageElement->assertStatus($status);
    }

    /**
     * Retrieves RFQ id from current url and stores it under named variable
     * Useful for direct route proceeding afterwards
     *
     * Example: I remember Request id as "Submitted request Id"
     *
     * @Then /^I remember RFQ id as "(?P<varName>[^"]*)"$/
     * @Then /^I remember Request id as "(?P<varName>[^"]*)"$/
     * @param string $varName
     */
    public function iRememberRequestIdAs($varName)
    {
        $urlPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        $urlPath = preg_replace('/^.*\.php/', '', $urlPath);
        $route = $this->getAppContainer()->get('router')->match($urlPath);

        $page = $this->getRequestViewPageByRoute($route['_route']);
        self::assertInstanceOf(RequestViewPage::class, $page, 'Can not get ID. Not on a Request page.');

        $id = $route['id'];
        $this->variablesMapping[$varName] = $id;
    }

    /**
     * @param $route
     * @return RequestViewPage|null
     */
    private function getRequestViewPageByRoute($route)
    {
        foreach ($this->RFQViewPages as $pageName) {
            $page = $this->getPage($pageName);
            if ($page->getRoute() === $route) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Directly proceed to correct RFQ view page dependant on frontend or backend application provided
     * selects Request entity by given criteria (variable mapping can be used)
     *
     * @see \Oro\Bundle\RFPBundle\Tests\Behat\Context\FeatureContext::$RFQViewPages
     * @see \Oro\Bundle\RFPBundle\Tests\Behat\Context\FeatureContext::iRememberRequestIdAs
     *
     * Example: I open RFQ view page on backend with id "42"
     *
     * @Then /^I open RFQ view page on (?P<application>backend|frontend) with id "(?P<value>[^"]*)"$/
     *
     * @param string $application
     * @param string $value
     */
    public function iOpenRFQPageWithId($application, $value)
    {
        $value = isset($this->variablesMapping[$value]) ? $this->variablesMapping[$value] : $value;
        /** @var Request $request */
        $request = $this->getAppContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Request::class)->findOneBy(['id' => $value]);

        self::assertInstanceOf(
            Request::class,
            $request,
            sprintf('Can not find RFQ by %s=%s', 'id', $value)
        );

        $page = $this->getPage($this->RFQViewPages[$application]);

        $path = $this->getAppContainer()->get('router')->generate($page->getRoute(), ['id' => $request->getId()]);

        $this->visitPath($path);

        $this->waitForAjax();
    }
}
