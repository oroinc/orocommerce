<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var AccountGroupProductVisibilityResolved */
    protected $entity;

    /** @var AccountGroup */
    protected $accountGroup;

    /** @var Product */
    protected $product;

    /** @var Website */
    protected $website;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->product = new Product();
        $this->accountGroup = new AccountGroup();
        $this->website = new Website();
        $this->entity = new AccountGroupProductVisibilityResolved($this->website, $this->product, $this->accountGroup);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->entity, $this->accountGroup, $this->product, $this->website);
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->entity,
            [
                ['visibility', 0],
                ['sourceProductVisibility', new AccountGroupProductVisibility()],
                ['source', BaseProductVisibilityResolved::VISIBILITY_VISIBLE],
                ['category', new Category()]
            ]
        );
    }

    public function testGetWebsite()
    {
        $this->assertEquals($this->website, $this->entity->getWebsite());
    }

    public function testGetAccountGroup()
    {
        $this->assertEquals($this->accountGroup, $this->entity->getAccountGroup());
    }

    public function testGetProduct()
    {
        $this->assertEquals($this->product, $this->entity->getProduct());
    }
}
