<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\DataFixtures\LoadCustomerUserRoles;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

class ProductSearchForVisitorTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;

    protected function setUp()
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCustomerUserRoles::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_prices.yml'
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        $this->reindexProductData();
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productsearch']
        );

        $this->assertResponseContains('cget_product_search_visitor.yml', $response, true);
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'productsearch', 'id' => '<toString(@product1->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'Unsupported request.'
            ],
            $response,
            404
        );
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            [
                'entity'     => 'productsearch',
                'id'         => '<toString(@product1->id)>',
                'attributes' => [
                    'name' => 'Updated Product Name'
                ]
            ],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $data = [
            'data' => [
                'type'       => 'productsearch',
                'attributes' => [
                    'name' => 'New Product'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productsearch'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productsearch', 'id' => '<toString(@product1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'productsearch'],
            ['filter' => ['id' => '<toString(@product1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
