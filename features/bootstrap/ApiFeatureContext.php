<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__  . '/../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';


/**
 * Class Adapted from: https://github.com/philsturgeon/build-apis-you-wont-hate/blob/master/chapter12/app/tests/behat/features/bootstrap/FeatureContext.php
 *
 * Original credits to Phil Sturgeon (https://twitter.com/philsturgeon)
 * and Ben Corlett (https://twitter.com/ben_corlett).
 *
 * Secondary credits to Ryan Weaver (https://twitter.com/weaverryan) from https://knpuniversity.com
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
    protected $requestPayload;


    /**
     * Payload of the response
     *
     * @var string
     */
    protected $responsePayload;


    /**
     * The Guzzle client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;


    /**
     * The response of the HTTP request
     *
     * @var ResponseInterface
     */
    protected $response;


    /**
     * Headers sent with request
     *
     * @var array[]
     */
    protected $requestHeaders = array();


    /**
     * The last request that was used to make the response
     *
     * @var \GuzzleHttp\Psr7\Request
     */
    protected $lastRequest;


    /**
     *
     *
     * @var ConsoleOutput
     */
    private $output;


    /**
     * The current scope within the response payload
     * which conditions are asserted against.
     */
    protected $scope;


    /**
     * The user to use with HTTP basic authentication
     *
     * @var string
     */
    protected $authUser;

    /**
     * The password to use with HTTP basic authentication
     *
     * @var string
     */
    protected $authPassword;


    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     *
     */
    public function __construct()
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

        $options = array();

        if($this->authUser) {
            $options = ['auth' => [$this->authUser, $this->authPassword]];
        }

        // Send request
        $this->response = $this->client->send($this->lastRequest, $options);
    }


    /**
     * @Given /^I authenticate with user "([^"]*)" and password "([^"]*)"$/
     */
    public function iAuthenticateWithEmailAndPassword($email, $password)
    {
        $this->authUser = $email;
        $this->authPassword = $password;
    }


    /**
     * @Given /^I set the "([^"]*)" header to be "([^"]*)"$/
     */
    public function iSetTheHeaderToBe($headerName, $value)
    {
        $this->requestHeaders[$headerName] = $value;
    }

    /**
     * @Given /^the "([^"]*)" header should be "([^"]*)"$/
     */
    public function theHeaderShouldBe($headerName, $expectedHeaderValue)
    {
        $response = $this->getResponse();

        assertEquals($expectedHeaderValue, (string) $response->getHeader($headerName));
    }

    /**
     * @Given /^the "([^"]*)" header should exist$/
     */
    public function theHeaderShouldExist($headerName)
    {
        $response = $this->getResponse();

        assertTrue($response->hasHeader($headerName));
    }

    /**
     * @Then /^the "([^"]*)" property should equal "([^"]*)"$/
     */
    public function thePropertyEquals($property, $expectedValue)
    {
        $payload = $this->getScopePayload();
        $actualValue = $this->arrayGet($payload, $property);

        assertEquals(
            $expectedValue,
            $actualValue,
            "Asserting the [$property] property in current scope equals [$expectedValue]: ".json_encode($payload)
        );
    }



    /**
     * @Then /^the response status code should be (?P<code>\d+)$/
     */
    public function theResponseStatusCodeShouldBe($statusCode)
    {
        $response = $this->getResponse();

        assertEquals($statusCode,
            $response->getStatusCode(),
            sprintf('Expected status code "%s" does not match observed status code "%s"', $statusCode, $response->getStatusCode()));
    }


    /**
     * @Then /^scope into the first "([^"]*)" property$/
     */
    public function scopeIntoTheFirstProperty($scope)
    {
        $this->scope = "{$scope}.0";
    }

    /**
     * @Then /^scope into the "([^"]*)" property$/
     */
    public function scopeIntoTheProperty($scope)
    {
        $this->scope = $scope;
    }


    /**
     * @Then /^reset scope$/
     */
    public function resetScope()
    {
        $this->scope = null;
    }


    /**
     * @Then /^the "([^"]*)" property should contain "([^"]*)"$/
     */
    public function thePropertyShouldContain($property, $expectedValue)
    {
        $payload = $this->getScopePayload();
        $actualValue = $this->arrayGet($payload, $property);

        // if the property is actually an array, use JSON so we look in it deep
        $actualValue = is_array($actualValue) ? json_encode($actualValue, JSON_PRETTY_PRINT) : $actualValue;
        assertContains(
            $expectedValue,
            $actualValue,
            "Asserting the [$property] property in current scope contains [$expectedValue]: ".json_encode($payload)
        );
    }

    /**
     * @Given /^the "([^"]*)" property should not contain "([^"]*)"$/
     */
    public function thePropertyShouldNotContain($property, $expectedValue)
    {
        $payload = $this->getScopePayload();
        $actualValue = $this->arrayGet($payload, $property);

        // if the property is actually an array, use JSON so we look in it deep
        $actualValue = is_array($actualValue) ? json_encode($actualValue, JSON_PRETTY_PRINT) : $actualValue;
        assertNotContains(
            $expectedValue,
            $actualValue,
            "Asserting the [$property] property in current scope does not contain [$expectedValue]: ".json_encode($payload)
        );
    }

    /**
     * @Then /^the "([^"]*)" property should exist$/
     */
    public function thePropertyExists($property)
    {
        $payload = $this->getScopePayload();

        $message = sprintf(
            'Asserting the [%s] property exists in the scope [%s]: %s',
            $property,
            $this->scope,
            json_encode($payload)
        );

        assertTrue($this->arrayHas($payload, $property), $message);
    }

    /**
     * @Then /^the "([^"]*)" property should not exist$/
     */
    public function thePropertyDoesNotExist($property)
    {
        $payload = $this->getScopePayload();

        $message = sprintf(
            'Asserting the [%s] property does not exist in the scope [%s]: %s',
            $property,
            $this->scope,
            json_encode($payload)
        );

        assertFalse($this->arrayHas($payload, $property), $message);
    }

    /**
     * @Then /^the "([^"]*)" property should be an array$/
     */
    public function thePropertyIsAnArray($property)
    {
        $payload = $this->getScopePayload();

        $actualValue = $this->arrayGet($payload, $property);

        assertTrue(
            is_array($actualValue),
            "Asserting the [$property] property in current scope [{$this->scope}] is an array: ".json_encode($payload)
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be an object$/
     */
    public function thePropertyIsAnObject($property)
    {
        $payload = $this->getScopePayload();

        $actualValue = $this->arrayGet($payload, $property);

        assertTrue(
            is_object($actualValue),
            "Asserting the [$property] property in current scope [{$this->scope}] is an object: ".json_encode($payload)
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be an empty array$/
     */
    public function thePropertyIsAnEmptyArray($property)
    {
        $payload = $this->getScopePayload();
        $scopePayload = $this->arrayGet($payload, $property);

        assertTrue(
            is_array($scopePayload) and $scopePayload === array(),
            "Asserting the [$property] property in current scope [{$this->scope}] is an empty array: ".json_encode($payload)
        );
    }

    /**
     * @Then /^the "([^"]*)" property should contain (\d+) item(?:|s)$/
     */
    public function thePropertyContainsItems($property, $count)
    {
        $payload = $this->getScopePayload();

        assertCount(
            $count,
            $this->arrayGet($payload, $property),
            "Asserting the [$property] property contains [$count] items: ".json_encode($payload)
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be an integer$/
     */
    public function thePropertyIsAnInteger($property)
    {
        $payload = $this->getScopePayload();

        isType(
            'int',
            $this->arrayGet($payload, $property),
            "Asserting the [$property] property in current scope [{$this->scope}] is an integer: ".json_encode($payload)
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be a string$/
     */
    public function thePropertyIsAString($property)
    {
        $payload = $this->getScopePayload();

        isType(
            'string',
            $this->arrayGet($payload, $property, true),
            "Asserting the [$property] property in current scope [{$this->scope}] is a string: ".json_encode($payload)
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be a string equalling "([^"]*)"$/
     */
    public function thePropertyIsAStringEqualling($property, $expectedValue)
    {
        $payload = $this->getScopePayload();

        $this->thePropertyIsAString($property);

        $actualValue = $this->arrayGet($payload, $property);

        assertSame(
            $actualValue,
            $expectedValue,
            "Asserting the [$property] property in current scope [{$this->scope}] is a string equalling [$expectedValue]."
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be a boolean$/
     */
    public function thePropertyIsABoolean($property)
    {
        $payload = $this->getScopePayload();

        assertTrue(
            gettype($this->arrayGet($payload, $property)) == 'boolean',
            "Asserting the [$property] property in current scope [{$this->scope}] is a boolean."
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be a boolean equalling "([^"]*)"$/
     */
    public function thePropertyIsABooleanEqualling($property, $expectedValue)
    {
        $payload = $this->getScopePayload();
        $actualValue = $this->arrayGet($payload, $property);

        if (! in_array($expectedValue, array('true', 'false'))) {
            throw new \InvalidArgumentException("Testing for booleans must be represented by [true] or [false].");
        }

        $this->thePropertyIsABoolean($property);

        assertSame(
            $actualValue,
            $expectedValue == 'true',
            "Asserting the [$property] property in current scope [{$this->scope}] is a boolean equalling [$expectedValue]."
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be an integer equalling "([^"]*)"$/
     */
    public function thePropertyIsAIntegerEqualling($property, $expectedValue)
    {
        $payload = $this->getScopePayload();
        $actualValue = $this->arrayGet($payload, $property);

        $this->thePropertyIsAnInteger($property);

        assertSame(
            $actualValue,
            (int) $expectedValue,
            "Asserting the [$property] property in current scope [{$this->scope}] is an integer equalling [$expectedValue]."
        );
    }

    /**
     * @Then /^the "([^"]*)" property should be either:$/
     */
    public function thePropertyIsEither($property, PyStringNode $options)
    {
        $payload = $this->getScopePayload();
        $actualValue = $this->arrayGet($payload, $property);

        $valid = explode("\n", (string) $options);

        assertTrue(
            in_array($actualValue, $valid),
            sprintf(
                "Asserting the [%s] property in current scope [{$this->scope}] is in array of valid options [%s].",
                $property,
                implode(', ', $valid)
            )
        );
    }



    /**
     * Checks the response exists and returns it.
     *
     * @throws Exception
     * @return ResponseInterface
     */
    protected function getResponse()
    {
        if (! $this->response) {
            throw new \Exception("You must first make a request to check a response.");
        }

        return $this->response;
    }


    /**
     * Return the response payload from the current response.
     *
     * @throws Exception
     */
    protected function getResponsePayload()
    {
        if (! $this->responsePayload) {
            $json = json_decode($this->getResponse()->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $message = 'Failed to decode JSON body ';

                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $message .= '(Maximum stack depth exceeded).';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $message .= '(Underflow or the modes mismatch).';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $message .= '(Unexpected control character found).';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $message .= '(Syntax error, malformed JSON): '."\n\n".$this->getResponse()->getBody();
                        break;
                    case JSON_ERROR_UTF8:
                        $message .= '(Malformed UTF-8 characters, possibly incorrectly encoded).';
                        break;
                    default:
                        $message .= '(Unknown error).';
                        break;
                }

                throw new Exception($message);
            }

            $this->responsePayload = $json;
        }

        return $this->responsePayload;
    }


    /**
     * Returns the payload from the current scope within
     * the response.
     *
     * @return mixed
     */
    protected function getScopePayload()
    {
        $payload = $this->getResponsePayload();

        if (! $this->scope) {
            return $payload;
        }

        return $this->arrayGet($payload, $this->scope, true);
    }


    /**
     * Get an item from an array using "dot" notation.
     *
     * Adapted further in this project
     *
     * @copyright   Taylor Otwell
     * @link        http://laravel.com/docs/helpers
     * @param       array   $array
     * @param       string  $key
     * @param bool  $throwOnMissing
     * @param bool  $checkForPresenceOnly If true, this function turns into arrayHas
     *                                    it just returns true/false if it exists
     * @return mixed
     * @throws Exception
     */
    public static function arrayGet($array, $key, $throwOnMissing = false, $checkForPresenceOnly = false)
    {
        // this seems like an odd case :/
        if (is_null($key)) {
            return $checkForPresenceOnly ? true : $array;
        }

        foreach (explode('.', $key) as $segment) {

            if (is_object($array)) {
                if (!property_exists($array, $segment)) {
                    if ($throwOnMissing) {
                        throw new \Exception(sprintf('Cannot find the key "%s"', $key));
                    }

                    // if we're checking for presence, return false - does not exist
                    return $checkForPresenceOnly ? false : null;
                }
                $array = $array->{$segment};

            } elseif (is_array($array)) {
                if (! array_key_exists($segment, $array)) {
                    if ($throwOnMissing) {
                        throw new \Exception(sprintf('Cannot find the key "%s"', $key));
                    }

                    // if we're checking for presence, return false - does not exist
                    return $checkForPresenceOnly ? false : null;
                }
                $array = $array[$segment];
            }
        }

        // if we're checking for presence, return true - *does* exist
        return $checkForPresenceOnly ? true : $array;
    }


    /**
     * Same as arrayGet (handles dot.operators), but just returns a boolean
     *
     * @param $array
     * @param $key
     * @return boolean
     */
    protected function arrayHas($array, $key)
    {
        return $this->arrayGet($array, $key, false, true);
    }


    /**
     * @Given /^print last response$/
     */
    public function printLastResponse()
    {
        if ($this->response) {

            // Build the first line of the response (protocol, protocol version, statuscode, reason phrase)
            $response = 'HTTP/1.1 ' . $this->response->getStatusCode() . ' ' . $this->response->getReasonPhrase() . "\r\n";

            // Add the headers
            foreach($this->response->getHeaders() as $key => $value) {
                $response .= sprintf("%s: %s\r\n", $key, $value[0]);
            }

            // Add the response body
            $response .= $this->prettifyJson($this->response->getBody());

            // Print the response
            $this->printDebug($response);
        }
    }


    /**
     * Returns the prettified equivalent of the input if the input is valid JSON.
     * Returns the original input if it is not valid JSON.
     *
     * @param $input
     *
     * @return string
     * @throws Exception
     */
    private function prettifyJson($input)
    {
        $decodedJson = json_decode($input);

        if($decodedJson === null) { // JSON is invalid
            return $input;
        }

        return json_encode($decodedJson, JSON_PRETTY_PRINT);
    }


    public function printDebug($string)
    {
        $this->getOutput()->writeln($string);
    }


    /**
     * @return ConsoleOutput
     */
    private function getOutput()
    {
        if ($this->output === null)  {
            $this->output = new ConsoleOutput();
        }

        return $this->output;
    }



    /**
     * Asserts the the href of the given link name equals this value
     *
     * Since we're using HAL, this would look for something like:
     *      "_links.programmer.href": "/api/programmers/Fred"
     *
     * @Given /^the link "([^"]*)" should exist and its value should be "([^"]*)"$/
     */
    public function theLinkShouldExistAndItsValueShouldBe($linkName, $url)
    {
        $this->thePropertyEquals(
            sprintf('_links.%s.href', $linkName),
            $url
        );
    }

    /**
     * @Given /^the embedded "([^"]*)" should have a "([^"]*)" property equal to "([^"]*)"$/
     */
    public function theEmbeddedShouldHaveAPropertyEqualTo($embeddedName, $property, $value)
    {
        $this->thePropertyEquals(
            sprintf('_embedded.%s.%s', $embeddedName, $property),
            $value
        );
    }
}
