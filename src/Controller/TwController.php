<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use App\Service\TwitterClient;

class TwController extends AbstractController
{
    /**
     * @Route("/tw", name="tw")
     */
    public function index()
    {
        $twitterClient = new TwitterClient();
        $baseTokens = $twitterClient->call('oauth', 'oauth/request_token', 'POST');
        parse_str($baseTokens, $decodedTokens);
        $authToken = $decodedTokens['oauth_token'];
        return $this->json([
            'data' => $authToken
        ]);
    }

    private function generateNonce():string {
        $randBytes = random_bytes(32);
        $b64random = base64_encode($randBytes);
        return preg_replace("/[^A-Za-z0-9 ]/", '', $b64random); //strip out non-alphanumeric
    }

    /**
     * @Route("/tw/connect", methods={"GET", "POST"})
     */
    public function connect(Request $request)
    {

        $body = json_decode($request->getContent(), true);
        $params = [
            'oauth_verifier' => $body['pin']
        ];

        $twitterClient = new TwitterClient($body['mainToken']);
        $userValues = $twitterClient->call('oauth', 'oauth/access_token', 'POST', $params);
        parse_str($userValues, $userValues);
        return $this->json([
            'data' => $userValues,
        ]);
    }

    /**
     * @Route("/tw/tweets", methods={"GET", "POST"})
     */
    public function tweets(Request $request)
    {
        $body = json_decode($request->getContent(), true);

        $twitterClient = new TwitterClient($body['oauth_token'], $body['oauth_token_secret']);
        $feed = $twitterClient->call('oauth', '1.1/statuses/user_timeline.json', 'GET');

        return $this->json([
            'data' => json_decode($feed, true, 512),
        ]);
    }


    /**
     * @Route("/tw/like", methods={"GET", "POST"})
     */
    public function like(Request $request)
    {
        $body = json_decode($request->getContent(), true);

        $userData = $body['userValues'];
        $twitterClient = new TwitterClient($userData['oauth_token'], $userData['oauth_token_secret']);
        $params = [
            'id' => (string)$body['tweetId']
        ];

        $like = $twitterClient->call('oauth', '1.1/favorites/create.json', 'POST', $params);

        return $this->json([
            'data' => $like,
        ]);
    }

}
