<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

class TwController extends AbstractController
{
    /**
     * @Route("/tw", name="tw")
     */
    public function index()
    {

        $twitterKey = $_ENV['TWITTER_KEY'];
        $twitterSecret = $_ENV['TWITTER_SECRET'];

        $nonce = $this->generateNonce();
        $httpMethod = 'POST';
        $url = 'https://api.twitter.com/oauth/request_token';
        $clientParams = [
            'oauth_consumer_key' => $twitterKey,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' =>  $nonce,
            'oauth_version' => '1.0',
            'oauth_callback' => "oob"
        ];

        ksort($clientParams); //sort alphabetically by key;
        $parameterString = http_build_query($clientParams);


        $signingBase = strtoupper($httpMethod) . "&" . urlencode($url) . "&" . urlencode($parameterString);
        $signingKey = "$twitterSecret&";
        $signature = base64_encode(hash_hmac("sha1", $signingBase, $signingKey, true));

        $headerString = "OAuth ";

        $overloadedClientParams = $clientParams;
        $overloadedClientParams['oauth_signature'] = $signature;
        $paramCount = count($overloadedClientParams);
        $tracker = 0;

        foreach($overloadedClientParams as $key => $value){
            $headerString .= urlencode($key) . '="' . urlencode($value) . '"';
            if (++$tracker !== $paramCount) {
                $headerString .= ", ";
            }
        }

        //die(var_dump($headerString));

        $client = HttpClient::create();


        $response = $client->request('POST', $url,
            [
                'headers' => ['Authorization' => $headerString]
            ]);
        $toReturn = $response->getContent();

        die(var_dump($toReturn));


        /*
                curl --location --request POST 'https://api.twitter.com/oauth/request_token' \
            --header 'Authorization: OAuth oauth_consumer_key="jyEhs5zul8srRzpd7YaLU7mlR",oauth_signature_method="HMAC-SHA1",oauth_timestamp="1592941261",oauth_nonce="m976Pw3plsG",oauth_version="1.0",oauth_callback="oob",oauth_signature="0ZIrR5JG8FQTinidgQivim%2BrtFg%3D"' \
            --header 'Cookie: personalization_id="v1_Tk8/8Ea2YyYak5B++tf1rg=="; guest_id=v1%3A159293987984890844'

                */



        return $this->json([
            'message' => 'Woopsie daisy',
            'key' => $twitterKey,
            'secret' => $twitterSecret,
            'random' => $clientParams
        ]);
    }

    private function generateNonce():string {
        $randBytes = random_bytes(32);
        $b64random = base64_encode($randBytes);
        $nonce = preg_replace("/[^A-Za-z0-9 ]/", '', $b64random); //strip out non-alphanumeric
        return $nonce;
    }

    /**
     * @Route("/tw/show/{id}", methods={"GET","HEAD", "POST"})
     */
    public function connect(string $id)
    {
        return $this->json([
            'as' => 'adasdsadsa'
        ]);
    }

}
