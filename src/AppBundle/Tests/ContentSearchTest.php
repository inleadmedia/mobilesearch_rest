<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

class ContentSearchTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    const AGENCY = '999999';
    const URI = '/content/search';

    /**
     * Search without all parameters.
     */
    public function testMissingParameters()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => '',
            'query' => '',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertFalse($result['status']);
        $this->assertCount(0, $result['items']);
    }

    /**
     * Search by type.
     */
    public function testTypeSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_news',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);
        $this->assertTrue($result['status']);
        $this->assertCount(3, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
        }
    }

    /**
     * Search by partial query.
     */
    public function testPartialSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);
        $this->assertTrue($result['status']);
        $this->assertCount(7, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
        }
    }

    /**
     * Search by regex.
     */
    public function testRegexSearch()
    {
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'fields.title.value',
            'query' => '^[a-z]',
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);
        $this->assertTrue($result['status']);
        $this->assertCount(7, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
        }
    }

    /**
     * Asserts item structure in the response.
     *
     * @param array $item   One item from the result set.
     */
    private function assertItemStructure(array $item)
    {
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('nid', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('changed', $item);
        // Attempt to parse a meaningful date format.
        $this->assertInternalType('int', strtotime($item['changed']));
    }

    /**
     * {@inheritdoc}
     */
    public function getFixtures(): array
    {
        return [
            new AgencyFixtures(),
            new ContentFixtures(),
        ];
    }
}
