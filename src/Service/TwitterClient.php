<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpClient\HttpClient;

class TwitterClient
{
    private $key;
    private $secret;
    private $userSecret = null;
    private $clientParams = [];
    private $host = 'https://api.twitter.com/';
    private $allowedAuthMethods = ['oauth', 'oauth2', 'bearer', 'basic'];
    private $allowedHttpVerbs = ['GET', 'PUT', 'POST', 'DELETE'];
    private $httpMethod = 'GET'; //do a get if we're to lazy to specify anything
    private $endpoint;

    /**
     * TwitterClient constructor.
     * @param string|null $oauthToken null if making app token request, otherwise contains entity's oauth token
     * @param string|null $userSecret if above token is a user's token, this is required to build the signature
     */
    public function __construct(
        ?string $oauthToken = null,
        ?string $userSecret = null
    ) {
        $this->key = $_ENV['TWITTER_KEY'];
        $this->secret = $_ENV['TWITTER_SECRET'];

        if(!is_null($userSecret)){
            $this->userSecret = $userSecret;
        }

        $this->setOauthParams($oauthToken);
    }

    /**
     * configures the client with the parameters for oAuth
     * @param string|null $oauthToken optional parameter to impersonate an entity if necessary
     */
    private function setOauthParams(?string $oauthToken): void {
        $params = [
           'oauth_consumer_key' => $this->key,
           'oauth_signature_method' => 'HMAC-SHA1',
           'oauth_timestamp' => time(),
           'oauth_version' => '1.0',
           'oauth_callback' => "oob",
           'oauth_nonce' => self::makeNonce()
        ];

        if(!is_null($oauthToken)){
            $params['oauth_token'] = $oauthToken;
        }

        ksort($params); //order alphabetically by key to match spec
        $this->clientParams = $params;
    }

    /**
     * generates and returns a unique value for oAuth purposes
     * @return string
     */
    private static function makeNonce():string {
        try {
            $randBytes = random_bytes(32);
            $b64random = base64_encode($randBytes);
            $nonce = preg_replace("/[^A-Za-z0-9 ]/", '', $b64random); //strip out non-alphanumeric
        } catch (\Exception $e) {
            $nonce = null;
        }

        return $nonce;
    }

    /**
     * makes a call to the twitter api and returns the result
     * @param string $authMethod  for now , just pass oauth
     * @param string $endpoint the endpoing of the twitter api eg 1.1/statuses/user_timeline.json
     * @param string $httpMethod POST, GET, etc
     * @param array|null $params url parameters in key-value pair if necessary for the api call eg [max_id => 123]
     * @return string actual response from twitter, do with it what you will
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function call(string $authMethod, string $endpoint, string $httpMethod, ?array $params = []) {
        $httpMethod = strtoupper($httpMethod);

        if(!in_array($authMethod, $this->allowedAuthMethods)){
            $exceptionMessage = "Auth method not allowed, please use one of the following: ";
            $exceptionMessage .= implode(', ', $this->allowedAuthMethods);
            throw new Exception($exceptionMessage);
        }
        if(!in_array($httpMethod, $this->allowedHttpVerbs)){
            $exceptionMessage = "Http method not allowed, please use one of the following: ";
            $exceptionMessage .= implode(', ', $this->allowedHttpVerbs);
            throw new Exception($exceptionMessage);
        }

        if($endpoint[0] === '/'){
            $endpoint = substr($endpoint, 1);
        }

        if(sizeof($params) > 0){
            $endpoint .= "?";
            foreach ($params as $key => $value){
                $endpoint .= $key . "=" . $value . "&";
            }
        }

        $this->setHttpMethod($httpMethod);
        $this->setEndpoint($endpoint);

        $httpClient = HttpClient::create();
        $response = $httpClient->request(
            $this->httpMethod,
            $this->host . $this->endpoint,
            [
                'headers' => ['Authorization' => $this->getHeaderString()]
            ]
        );
        return $response->getContent();
    }

    /**
     * builds signing base string to encrypt later
     * @return string
     */
    private function buildSigningBase(): string {
        $parameterString = http_build_query($this->clientParams);
        return  "$this->httpMethod&" . urlencode($this->host . $this->endpoint) . "&" . urlencode($parameterString);
    }

    /**
     * configures the client with the http method passed along to the call() method
     * @param $httpMethod
     */
    private function setHttpMethod($httpMethod): void {
        $this->httpMethod = $httpMethod;
    }

    /**
     * configures the client with the endpoint passed along to the call() method
     * @param $endpoint
     */
    private function setEndpoint($endpoint): void {
        $this->endpoint = $endpoint;
    }

    /**
     * generates key for signing the authorization header string
     * based on what secret keys we have
     * @return string
     */
    private function buildSigningKey(): string {
        return "$this->secret&$this->userSecret";
    }

    /**
     * hashes the prepared values and creates a signature
     * @return string
     */
    private function getSignature(): string {
        return base64_encode(hash_hmac("sha1", $this->buildSigningBase(), $this->buildSigningKey(), true));
    }

    /**
     * gives full oAuth header string
     * @return string
     */
    private function getHeaderString(): string {
        //TODO: actually do multiple types of Authentication
        $headerString = "OAuth ";

        $overloadedClientParams = $this->clientParams;
        $overloadedClientParams['oauth_signature'] = $this->getSignature();
        $paramCount = count($overloadedClientParams);
        $tracker = 0;
        foreach($overloadedClientParams as $key => $value){
            $headerString .= urlencode($key) . '="' . urlencode($value) . '"';
            if (++$tracker !== $paramCount) {
                $headerString .= ", ";
            }
        }

        return $headerString;
    }
}