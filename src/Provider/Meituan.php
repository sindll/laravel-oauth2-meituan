<?php

namespace Sindll\OAuth2\Client\Provider;

use GuzzleHttp\Psr7;
use UnexpectedValueException;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Client;

class Meituan extends AbstractProvider
{
	use QueryBuilderTrait;

	/**
     * @var string
     */
	private $urlRequestPrefix = 'https://waimaiopen.meituan.com';
    // private $urlRequestPrefix = 'http://wk.com/GS_XDWM_API/public';

	/**
     * @inheritdoc
     */
	public function getBaseAuthorizationUrl()
	{

	}

	/**
     * @inheritdoc
     */
	public function getBaseAccessTokenUrl(array $params)
	{

	}

	/**
     * @inheritdoc
     */
	public function getResourceOwnerDetailsUrl(AccessToken $token)
	{

	}

	/**
     * @inheritdoc
     */
	public function getDefaultScopes()
	{

	}

	/**
     * @inheritdoc
     */
	protected function checkResponse(ResponseInterface $response, $data)
	{
		if (!empty($data['error'])) {
            $code  = $data['error']['code'];
            $error = $data['error']['msg'];
            throw new IdentityProviderException($error, $code, $data);
        }
	}

	/**
     * @inheritdoc
     */
	protected function createResourceOwner(array $response, AccessToken $token)
	{

	}

	public function getBaseRquestPrefixUrl()
    {
        return $this->urlRequestPrefix;
    }

    protected function getRequestUrl($url, $params)
    {
        if (substr($url, 0, 4) != 'http') {
            $url = sprintf('%s/%s', trim($this->getBaseRquestPrefixUrl(), '/'), trim($url, '/'));
        }

        $time = time();
        // $time = 1668437802;

        $params['app_id']    = $this->clientId;
        $params['timestamp'] = $time;

        $query = [];
        $query['app_id']    = $this->clientId;
        $query['timestamp'] = $time;
        $query['sig']       = $this->sign($url, $params);

        $url = $this->appendQuery($url, $this->buildQueryString($query));

        return $url;
    }

    protected function sign($url, $params)
    {
        ksort($params);

        $tmp = [];
        foreach ($params as $key => $value) {
            $tmp[] = "$key=$value";
        }

        $string = $url.'?'.implode('&', $tmp).$this->clientSecret;

        $sign = md5($string);

        return $sign;
    }

    public function request($method, $url, array $params = [], array $headers = [])
    {
        $url = $this->getRequestUrl($url, $params);

        if ($method === self::METHOD_GET) {
            $url = $this->appendQuery($url, $this->buildQueryString($params));
        }

        $options = [];
        if ($headers) {
            $options['headers'] = $headers;
        }
        if ($method === self::METHOD_POST) {
            $options['headers'] = array_merge(['content-type' => 'application/x-www-form-urlencoded'], $headers);
            $options['body'] = $this->buildQueryString($params);
        }

        $request = $this->getRequest($method, $url, $options);


        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }

    public function upload($url, array $params = [], array $options = [])
    {
        $url = $this->getRequestUrl($url, $params);

        $client = new Client();

        $request = $client->request('POST', $url, $options);

        $response = $this->parseResponse($request);

        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }
}
