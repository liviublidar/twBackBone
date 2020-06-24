<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use App\Service\TwitterClient;

class TwController extends AbstractController
{
    /**
     * @Route("/tw", name="tw")
     */
    public function index()
    {

        $twitterClient = new TwitterClient();

        die(var_dump($twitterClient->call('oauth', 'oauth/request_token', 'POST')));

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
