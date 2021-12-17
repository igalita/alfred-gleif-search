<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Igalita\Gleif\Search;
use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{

    /** @test */
    public function it_returns_proper_message_if_search_fails()
    {

        $client = $this->mockClient(new Response(404));

        $search = new Search($client);

        $response = $search->get('foo');

        $this->assertEquals(json_decode($response, true), [
            'items' => [
                [
                    'subtitle' => 'Failed to connect to GLEIF API.',
                    'title' => 'Request failed',
                    'type' => 'default',
                    'valid' => false
                ]
            ]
        ]);
    }

    /** @test */
    public function it_returns_proper_message_if_no_results()
    {
        $client = $this->mockClient(new Response(200, [], json_encode([
            'data' => []
        ])));

        $search = new Search($client);

        $response = $search->get('foo');

        $this->assertEquals(json_decode($response, true), [
            'items' => [
                [
                    'subtitle' => 'No Legal Entities found for "foo"',
                    'title' => 'No results',
                    'type' => 'default',
                    'valid' => false
                ]
            ]
        ]);
    }

    /** @test */
    public function it_returns_all_valid_results()
    {
        $client = $this->mockClient(new Response(200, [], json_encode([
            'data' => [
                [
                    'attributes' => [
                        'value' => 'foo'
                    ],
                    'relationships' => [
                        'lei-records' => [
                            'data' => [
                                'id' => 'foos'
                            ]
                        ]
                    ]
                ],
                [
                    'attributes' => [
                        'value' => 'bar'
                    ],
                    'relationships' => [
                        'lei-records' => [
                            'data' => [
                                'id' => 'bars'
                            ]
                        ]
                    ]
                ]
            ]
        ])));

        $search = new Search($client);

        $response = $search->get('foo');

        $this->assertEquals(json_decode($response, true), [
            'items' => [
                [
                    'arg' => $search::SEARCH_TOOL_URL . "/foos," . $search::API_URL . "/foos",
                    'autocomplete' => 'foo',
                    'title' => 'foo',
                    'uid' => 'foo',
                    'valid' => true
                ],
                [
                    'arg' => $search::SEARCH_TOOL_URL . "/bars," . $search::API_URL . "/bars",
                    'autocomplete' => 'bar',
                    'title' => 'bar',
                    'uid' => 'bar',
                    'valid' => true
                ]
            ]
        ]);
    }

    /**
     * @return Client
     */
    protected function mockClient(Response $response): Client
    {
        $mock = new MockHandler([
            $response
        ]);

        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }
}
