<?php

namespace AppBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractBaseTest extends WebTestCase
{
    private $client;

    private $container;

    public function getClient()
    {
        return $this->client;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sends a request.
     *
     * @param string $uri           URI target to send request.
     * @param array $parameters     Request parameters.
     * @param string $method        Request method.
     *
     * @return Response
     */
    public function request($uri, array $parameters, $method = 'GET')
    {
        $client = $this->getClient();
        $client->request($method, $uri, $parameters);

        /** @var Response $response */
        $response = $client->getResponse();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
    }
}
