<?php
/**
 * package:  Toristy Booking
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Apis;

class Client
{
    private $Key;

    private $Endpoints = [
        "https://api.toristy.com"
    ];

    private $Endpoint;

    /**
     * Client constructor.
     *
     * @param  string  $key  api-key
     * @param  int  $flag  0: api.toristy.com
     */
    public function __construct(string $key, int $flag = 0)
    {
        $this->Key = $key;
        $this->Endpoint = (array_key_exists($flag, $this->Endpoints)) ? $this->Endpoints[$flag] : $this->Endpoints[0];
    }

    /**
     * Get the content from the server.
     *
     * @param  array  $params  the url parameters which complete a url.
     * @param  array  $queries  the query parameters on a url.
     *
     * @return Response
     */
    public function Get(array $params = [], array $queries = []): Response
    {
        if (isset($queries["apikey"])) {
            unset($queries["apikey"]);
        }
        $data   = ["apikey" => $this->Key] + $queries;
        $config = [
            "url"  => $this->CreateUrl($params, $data),
            "type" => "GET"
        ];
        return $this->Populate($config);
    }

    /**
     * Generate the request url with paths and queries.
     *
     * @param  array  $params
     * @param  array  $queries
     *
     * @return string
     */
    private function CreateUrl(array $params = [], array $queries = []): string
    {
        $url = $this->Endpoint;
        if ( ! empty($params)) {
            $url = "$url/".implode("/", $params);
        }
        if ( ! empty($queries)) {
            foreach ($queries as $key => $val) {
                $url .= (strpos($url, "?") === false) ? "?$key=$val" : "&$key=$val";
            }
        }
        return $url;
    }

    /**
     * Populate Response class with the request data and response data.
     *
     * @param  array  $config
     *
     * @return Response
     */
    private function Populate(array $config): Response
    {
        $response = new Response($config);
        $response->Populate();

        return $response;
    }

    private function Post(array $args = []): Client
    {
        return $this;
    }
}