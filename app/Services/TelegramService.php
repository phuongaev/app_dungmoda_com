<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class TelegramService {

    protected $tele_api;

    public function __construct()
    {
        $this->tele_api = "https://api.telegram.org/bot" . config('telegram.token');
    }

    public function sendMessage ($message)
    {
        $client = new Client();
        $response = $client->request("POST", $this->tele_api . "/sendMessage", [
            "headers" => [
                "Content-type" => "application/json; charset=UTF-8"
            ],
            "json" => [
                "chat_id" => config('telegram.chat_id'),
                "text" => $message
            ]
        ]);

        return $response->getBody()->getContents();
    }

    public function setWebhook($url)
    {
        $client = new Client();
        $response = $client->request("POST", $this->tele_api . "/setWebhook", [
            "form_params" => [
                "url" => $url,
            ]
        ]);
        return $response->getBody()->getContents();
    }
}
