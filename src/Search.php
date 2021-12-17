<?php

namespace Igalita\Gleif;

use Alfred\Workflows\Workflow;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Throwable;

class Search
{
    /**
     * @var Workflow;
     */
    protected $workflow;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var Client
     */
    protected $client;

    const AUTOCOMPLETION_URL = "https://api.gleif.org/api/v1/autocompletions?field=fulltext&q=%s";

    const SEARCH_TOOL_URL = "https://search.gleif.org/#/record";

    const API_URL = "https://api.gleif.org/api/v1/lei-records";

    /**
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->workflow = new Workflow;
        $this->client = $client ?? new Client(['timeout'  => 5]);
    }

    /**
     * @param string $query
     * @return string
     */
    public function get(string $query): string
    {
        $this->query = $query;

        try {
            $resp = $this->client->get(sprintf(self::AUTOCOMPLETION_URL, urlencode($this->query)));

            $data = json_decode($resp->getBody(), true)['data'];

            if (empty($data)) {
                return $this->noResults($query);
            }

            foreach ($data as $value) {
                $this->workflow->result()
                    ->uid($value['attributes']['value'])
                    ->title($value['attributes']['value'])
                    ->arg($this->buildUrls($value['relationships']['lei-records']['data']['id']))
                    ->autocomplete($value['attributes']['value'])
                    ->valid(true);
            }

            return $this->workflow->output();
        } catch (ClientException $e) {
            return $this->failed();
        } catch (Throwable $e) {
            return $this->failed();
        }
    }

    /**
     * @return string
     */
    protected function failed(): string
    {
        $this->workflow->result()
            ->title('Request failed')
            ->subtitle('Failed to connect to GLEIF API.')
            ->type('default')
            ->valid(false);

        return $this->workflow->output();
    }

    /**
     * @var string $query
     * @return string
     */
    protected function noResults(string $query): string
    {
        $this->workflow->result()
            ->title('No results')
            ->subtitle("No Legal Entities found for \"{$query}\"")
            ->type('default')
            ->valid(false);

        return $this->workflow->output();
    }

    /**
     * @param string $leiCode
     * @return string
     */
    protected function buildUrls(string $leiCode = null): string
    {
        $urls = [];

        $urls[] = self::SEARCH_TOOL_URL . "/" . $leiCode;
        $urls[] = self::API_URL . "/" . $leiCode;

        return implode(',', $urls);
    }
}
