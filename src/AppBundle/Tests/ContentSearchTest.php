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

        $result = $this->assertResponse($response);

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

        $result = $this->assertResponse($response);

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

        $result = $this->assertResponse($response);

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

        $result = $this->assertResponse($response);

        $this->assertTrue($result['status']);
        $this->assertCount(7, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertItemStructure($item);
        }
    }

    /**
     * Fetch limited results.
     */
    public function testLimitedSearch()
    {
        $amount = 3;
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_',
            'amount' => $amount,
        ];

        /** @var Response $response */
        $response = $this->request(self::URI, $parameters, 'GET');

        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertResponseStructure($result);
        $this->assertTrue($result['status']);
        $this->assertCount($amount, $result['items']);
    }

    /**
     * Fetches paged search results.
     */
    public function testPagedSearch()
    {
        $amount = 2;
        $skip = 0;
        $parameters = [
            'agency' => self::AGENCY,
            'field' => 'type',
            'query' => 'ding_',
            'amount' => $amount,
            'skip' => $skip,
        ];

        $results = [];

        while (true) {
            /** @var Response $response */
            $response = $this->request(self::URI, $parameters, 'GET');

            $this->assertEquals(200, $response->getStatusCode());

            $result = json_decode($response->getContent(), true);

            $this->assertResponseStructure($result);

            if (empty($result['items'])) {
                break;
            }

            $this->assertLessThanOrEqual($amount, count($result['items']));

            foreach ($result['items'] as $item) {
                // Node id's normally should not repeat for same agency.
                $this->assertNotContains($item['nid'], $results);
                $results[] = $item['nid'];
            }

            $skip += $amount;
            $parameters['skip'] = $skip;
        }

        $this->assertCount(7, $results);
        // Expect zero, since we reached end of the list.
        $this->assertEquals(0, count($result['items']));
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
        $this->assertArrayHasKey('type', $item);
        // Attempt to parse a meaningful date format, also it has to be in ISO-8601 format.
        $this->assertIsoDate($item['changed']);

        // Events have date in response.
        if ('ding_event' == $item['type']) {
            $this->assertArrayHasKey('event_date', $item);
            $this->assertArrayHasKey('from', $item['event_date']);
            $this->assertIsoDate($item['event_date']['from']);
            $this->assertArrayHasKey('to', $item['event_date']);
            $this->assertIsoDate($item['event_date']['to']);
            $this->assertArrayHasKey('all_day', $item['event_date']);
            $this->assertInternalType('boolean', $item['event_date']['all_day']);
        }
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
