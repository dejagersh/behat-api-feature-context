<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Adapted from: https://github.com/philsturgeon/build-apis-you-wont-hate/blob/master/chapter12/app/tests/behat/features/bootstrap/FeatureContext.php
 *
 * Original credits to Phil Sturgeon (https://twitter.com/philsturgeon)
 * and Ben Corlett (https://twitter.com/ben_corlett).
 *
 *
 * A Behat context aimed at doing one awesome thing: interacting with APIs
 */
class ApiFeatureContext implements Context
{
    /**
     * Payload of the request
     *
     * @var string
     */
    private $requestPayload;


    /**
     * The Guzzle client
     *
     * @var \GuzzleHttp\Client
     */
    private $client;


    /**
     * The response of the HTTP request
     *
     * @var ResponseInterface
     */
    private $response;


    /**
     * Headers sent with request
     *
     * @var array[]
     */
    private $requestHeaders;


    /**
     * The last request that was used to make the response
     *
     * @var \GuzzleHttp\Psr7\Request
     */
    private $lastRequest;


    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct($parameters)
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost:8000'
        ]);
    }

    /**
     * @Given I have the payload
     */
    public function iHaveThePayload(PyStringNode $requestPayload)
    {
        $this->requestPayload = $requestPayload;
    }


    /**
     * @When /^I request "(GET|PUT|POST|DELETE|PATCH) ([^"]*)"$/
     */
    public function iRequest($httpMethod, $resource)
    {
        $method = strtoupper($httpMethod);

        // Construct request
        $this->lastRequest = new Request($method, $resource, $this->requestHeaders, $this->requestPayload);

        // Send request
        $this->response = $this->client->send($this->lastRequest);
    }


    /**
     * @Given /^I set the "([^"]*)" header to be "([^"]*)"$/
     */
    public function iSetTheHeaderToBe($headerName, $value)
    {
        $this->requestHeaders[$headerName] = $value;
    }



}
