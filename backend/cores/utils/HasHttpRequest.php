<?php

namespace cores\utils;

use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;


trait HasHttpRequest
{
    protected function get($endpoint, $query = [], $headers = [])
    {
        return $this->request('get', $endpoint, [
            'headers' => $headers,
            'query' => $query,
        ]);
    }

    protected function post($endpoint, $params = [], $query = [], $headers = [])
    {
        return $this->request('post', $endpoint, [
            'headers' => $headers,
            'query' => $query,
            'form_params' => $params,
        ]);
    }

    protected function postJson($endpoint, $params = [], $query = [], $headers = [])
    {
        $headers['content-type'] = 'application/json';
        return $this->request('post', $endpoint, [
            'headers' => $headers,
            'query' => $query,
            'body' => json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function httpDelete($endpoint, $params = [], $query = [], $headers = [])
    {
        return $this->request('delete', $endpoint, [
            'headers' => $headers,
            'query' => $query,
            'form_params' => $params
        ]);
    }

    protected function request($method, $endpoint, $options = [])
    {
        try {
            return $this->unwrapResponse($this->getHttpClient($this->getBaseOptions())->{$method}($endpoint, $options));
        } catch (Exception $e) {
            $response = [
                'code' => '-1',
                'msg' => $e->getMessage()
            ];
            return $response;
        }
//        return $this->unwrapResponse($this->getHttpClient($this->getBaseOptions())->{$method}($endpoint, $options));
    }


    protected function unwrapResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return json_decode(json_encode(simplexml_load_string($contents)), true);
        }

        return $contents;
    }

    protected function getHttpClient(array $options = [])
    {
        return new Client($options);
    }

    protected function getBaseOptions()
    {
        $options = [
            'base_uri' => method_exists($this, 'getBaseUri') ? $this->getBaseUri() : '',
            'timeout' => property_exists($this, 'timeout') ? $this->timeout : 300.0,
        ];

        return $options;
    }
}
