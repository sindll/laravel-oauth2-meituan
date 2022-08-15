<?php

namespace Sindll\OAuth2\Client\Provider;

use UnexpectedValueException;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\QueryBuilderTrait;

class Meituan extends AbstractProvider
{
	use QueryBuilderTrait;

	/**
     * @var string
     */
	private $urlRequestPrefix = 'https://waimaiopen.meituan.com';

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

    protected function getRequestUrl($url)
    {
        if (substr($url, 0, 4) == 'http') {
            return $url;
        }

        return sprintf('%s/%s', trim($this->getBaseRquestPrefixUrl(), '/'), trim($url, '/'));
    }

    protected function getRequestQuery($url, $params)
    {
    	$params['app_id']    = $this->clientId;
    	$params['timestamp'] = time();
    	$params['sig']       = $this->sign($url, $params);

        return $this->buildQueryString($params);
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
        $url = $this->getRequestUrl($url);


        if ($method === self::METHOD_GET) {
            $query = $this->getRequestQuery($url, $params);
            $url = $this->appendQuery($url, $query);
        }

        $options = [];
        if ($headers) {
            $options['headers'] = $headers;
        }
        if ($method === self::METHOD_POST) {
        	$options['body'] = json_encode($params);
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
}
