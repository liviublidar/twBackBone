<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpClient\HttpClient;

class TwitterClient
{
    private $key = null;
    private $secret = null;
    private $userSecret = null;
    private $clientParams = [];
    private $host = 'https://api.twitter.com/';
    private $allowedAuthMethods = ['oauth', 'oauth2', 'bearer', 'basic'];
    private $allowedHttpVerbs = ['GET', 'PUT', 'POST', 'DELETE'];
    private $httpMethod = 'GET'; //do a get if we're to lazy to specify anything
    private $endpoint;

    public function __construct(
        ?string $userSecret = null
    ) {
        $this->key = $_ENV['TWITTER_KEY'];;
        $this->secret = $_ENV['TWITTER_SECRET'];

        if(!is_null($userSecret)){
            $this->userSecret = $userSecret;
        }

        $this->setOauthParams();
    }

    private function setOauthParams(): void {
        $params = [
           'oauth_consumer_key' => $this->key,
           'oauth_signature_method' => 'HMAC-SHA1',
           'oauth_timestamp' => time(),
           'oauth_version' => '1.0',
           'oauth_callback' => "oob",
           'oauth_nonce' => self::makeNonce()
        ];

        ksort($params); //order alphabetically by key to match spec
        $this->clientParams = $params;
    }

    /**
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

    public function call(string $authMethod, string $endpoint, string $httpMethod) {
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

    private function buildSigningBase(): string {
        $parameterString = http_build_query($this->clientParams);
        return  "$this->httpMethod&" . urlencode($this->host . $this->endpoint) . "&" . urlencode($parameterString);
    }

    private function setHttpMethod($httpMethod): void {
        $this->httpMethod = $httpMethod;
    }

    private function setEndpoint($endpoint): void {
        $this->endpoint = $endpoint;
    }

    private function buildSigningKey(): string {
        return "$this->secret&$this->userSecret";
    }

    private function getSignature(): string {
        return base64_encode(hash_hmac("sha1", $this->buildSigningBase(), $this->buildSigningKey(), true));
    }

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