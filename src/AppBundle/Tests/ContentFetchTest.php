<?php

namespace AppBundle\Tests;

use AppBundle\DataFixtures\MongoDB\AgencyFixtures;
use AppBundle\DataFixtures\MongoDB\ContentFixtures;
use Symfony\Component\HttpFoundation\Response;

class ContentFetchTest extends AbstractFixtureAwareTest
{
    use AssertResponseStructureTrait;

    /**
     * Fetch with missing data.
     */
    public function testFetchEmpty()
    {
        $agency = '';
        $parameters = [
            'agency' => $agency,
        ];
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertFalse($result['status']);
        $this->assertEmpty($result['items']);
    }

    /**
     * Fetch by nid.
     */
    public function testFetchByNid()
    {
        $nid = 1000;
        $agency = '999999';
        $parameters = [
            'agency' => $agency,
            'node' => $nid,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertEquals(1, count($result['items']));
        $this->assertEquals($nid, $result['items'][0]['nid']);
        $this->assertEquals($agency, $result['items'][0]['agency']);
    }

    /**
     * Fetch by type.
     */
    public function testFetchByType()
    {
        $agency = '999999';
        $type = 'ding_news';
        $parameters = [
            'agency' => $agency,
            'type' => $type,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertEquals(3, count($result['items']));

        foreach ($result['items'] as $item) {
            $this->assertEquals($type, $item['type']);
            $this->assertEquals($agency, $item['agency']);
        }
    }

    /**
     * Fetch by library.
     */
    public function testFetchByLibrary()
    {
        $agency = '999999';
        $libraries = ['Alpha'];
        $parameters = [
            'agency' => $agency,
            'library' => $libraries,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        $previousCount = count($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertArraySubset($libraries, $item['fields']['og_group_ref']['value']);
        }

        // Having more than one library would yield more items than previously.
        $libraries = ['Alpha', 'Beta'];
        $parameters['library'] = $libraries;

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertGreaterThan($previousCount, count($result['items']));

        foreach ($result['items'] as $item) {
            $this->assertGreaterThanOrEqual(1, count(array_intersect($libraries, $item['fields']['og_group_ref']['value'])));
        }
    }

    /**
     * Default fetch.
     */
    public function testFetchAll()
    {
        $agency = '999999';
        $parameters = [
            'agency' => $agency,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        // 10 items are returned by default.
        $this->assertLessThan(11, count($result['items']));

        foreach ($result['items'] as $item) {
            $this->assertEquals($agency, $item['agency']);
        }
    }

    /**
     * Limited fetch.
     */
    public function testFetchSmallAmount()
    {
        $agency = '999999';
        $amount = 2;
        $parameters = [
            'agency' => $agency,
            'amount' => $amount,
        ];
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);
        $this->assertEquals($amount, count($result['items']));
    }

    /**
     * Paged fetch.
     */
    public function testPager()
    {
        $agency = '999999';
        $skip = 0;
        $amount = 2;
        $parameters = [
            'agency' => $agency,
            'amount' => $amount,
            'skip' => $skip,
        ];

        $node_ids = [];

        while (true) {
            /** @var Response $response */
            $response = $this->request('/content/fetch', $parameters, 'GET');

            $result = $this->assertResponse($response);

            if (empty($result['items'])) {
                break;
            }

            $this->assertLessThanOrEqual($amount, count($result['items']));

            foreach ($result['items'] as $item) {
                // Node id's normally should not repeat for same agency.
                $this->assertNotContains($item['nid'], $node_ids);
                $this->assertEquals($agency, $item['agency']);
                $node_ids[] = $item['nid'];
            }

            $skip += $amount;
            $parameters['skip'] = $skip;
        }

        $this->assertCount(7, $node_ids);
        // Expect zero, since we reached end of the list.
        $this->assertEquals(0, count($result['items']));
    }

    /**
     * Fetch sorted.
     */
    public function testSorting()
    {
        $agency = '999999';
        $sort = 'nid';
        $order = 'ASC';
        $parameters = [
            'agency' => $agency,
            'sort' => $sort,
            'order' => $order,
        ];

        // Ascending sort.
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $this->assertGreaterThan($result['items'][$i - 1][$sort], $result['items'][$i][$sort]);
        }

        // Descending sort.
        $parameters['order'] = 'DESC';

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $this->assertLessThan($result['items'][$i - 1][$sort], $result['items'][$i][$sort]);
        }
    }

    /**
     * Fetch sorted by complex field.
     */
    public function testNestedFieldSorting()
    {
        $agency = '999999';
        $sort = 'fields.title.value';
        $order = 'ASC';
        $parameters = [
            'agency' => $agency,
            'sort' => $sort,
            'order' => $order,
        ];

        // Ascending order.
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $first_node = $result['items'][$i];
            $second_node = $result['items'][$i - 1];
            $comparison = strcmp($first_node['fields']['title']['value'], $second_node['fields']['title']['value']);
            $this->assertGreaterThan(0, $comparison);
        }

        // Descending order;
        $parameters['order'] = 'DESC';

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertNotEmpty($result['items']);

        for ($i = 1; $i < count($result['items']); $i++) {
            $first_node = $result['items'][$i];
            $second_node = $result['items'][$i - 1];
            $comparison = strcmp($first_node['fields']['title']['value'], $second_node['fields']['title']['value']);
            $this->assertLessThan(0, $comparison);
        }
    }

    /**
     * Fetch upcoming events.
     */
    public function testUpcomingEvents()
    {
        $agency = '999999';
        $type = 'ding_event';
        $upcoming = true;
        $parameters = [
            'agency' => $agency,
            'type' => $type,
            'upcoming' => $upcoming,
        ];

        // Upcoming only.
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount(1, $result['items']);

        $event_node = reset($result['items']);
        $event_unixtime = strtotime($event_node['fields']['field_ding_event_date']['value']['to']);
        $this->assertNotEquals(-1, $event_unixtime);
        $this->assertGreaterThan(time(), $event_unixtime);

        // Fetch all.
        $parameters['upcoming'] = false;

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount(4, $result['items']);
    }

    /**
     * Fetch filtered by taxonomy.
     */
    public function testTaxonomyFiltering()
    {
        $agency = '999999';
        $parameters = [
            'agency' => $agency,
            'vocabulary' => [
                'field_ding_event_category'
            ],
            'terms' => [
                'Theta'
            ],
        ];

        // Check for nodes with 'Theta' term.
        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount(1, $result['items']);

        $found = [];
        foreach ($result['items'] as $item) {
            if ($this->keyExists($item['taxonomy']['field_ding_event_category']['terms'], 'Theta')) {
                $found[] = $item['nid'];
            }
        }

        $this->assertCount(1, $found);

        // Check for nodes with 'Alpha' term.
        $parameters['terms'] = [
            'Alpha',
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount(3, $result['items']);

        $found = [];
        foreach ($result['items'] as $item) {
            if ($this->keyExists($item['taxonomy']['field_ding_event_category']['terms'], 'Alpha')) {
                $found[] = $item['nid'];
            }
        }

        $this->assertCount(3, $found);

        // Check for nodes with either 'Delta' or 'Theta' terms.
        $parameters['vocabulary'] = [
            'field_ding_event_category',
            'field_ding_event_category',
        ];
        $parameters['terms'] = [
            'Delta',
            'Theta',
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');
        $this->assertEquals(200, $response->getStatusCode());

        $result = $this->assertResponse($response);

        $this->assertCount(2, $result['items']);

        $found = [];
        foreach ($result['items'] as $item) {
            if ($this->keyExists($item['taxonomy']['field_ding_event_category']['terms'], 'Delta')) {
                $found[] = $item['nid'];
            }
        }

        // Fixtures have two nodes with 'Delta' category terms.
        $this->assertCount(2, $found);

        $found = [];
        foreach ($result['items'] as $item) {
            if ($this->keyExists($item['taxonomy']['field_ding_event_category']['terms'], 'Theta')) {
                $found[] = $item['nid'];
            }
        }

        // Fixtures have one node with 'Theta' category term.
        $this->assertCount(1, $found);
    }

    /**
     * Fetch by complex filtering.
     */
    public function testFetchComplex()
    {
        $agency = '999999';
        $type = 'ding_news';
        $amount = 2;
        $skip = 1;
        $sort = 'fields.title.value';
        $order = 'DESC';
        $vocabulary = [
            'field_ding_news_category',
        ];
        $terms = [
            'Alpha',
        ];
        $parameters = [
            'agency' => $agency,
            'type' => $type,
            'amount' => $amount,
            'skip' => $skip,
            'sort' => $sort,
            'order' => $order,
            'vocabulary' => $vocabulary,
            'terms' => $terms,
        ];

        /** @var Response $response */
        $response = $this->request('/content/fetch', $parameters, 'GET');

        $result = $this->assertResponse($response);

        $this->assertCount($amount, $result['items']);

        // Check some static values.
        foreach ($result['items'] as $item) {
            $this->assertEquals($agency, $item['agency']);
            $this->assertEquals($type, $item['type']);
            $this->assertTrue($this->keyExists($item['taxonomy']['field_ding_news_category']['terms'], $terms[0]));
            $node_ids[] = $item['nid'];
        }

        // Check order.
        for ($i = 1; $i < count($result['items']); $i++) {
            $first_node = $result['items'][$i];
            $second_node = $result['items'][$i - 1];
            $comparison = strcmp($first_node['fields']['title']['value'], $second_node['fields']['title']['value']);
            $this->assertLessThan(0, $comparison);
        }
    }

    /**
     * Recursively searches an array for a key value.
     *
     * @param array $haystack   Array to search in.
     * @param $needle           Key to search.
     *
     * @return bool             True if key was found, false otherwise.
     */
    private function keyExists(array $haystack, $needle)
    {
        $iterator = new \RecursiveArrayIterator($haystack);
        $recursive = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                return true;
            }
        }

        return false;
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
