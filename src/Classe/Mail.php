<?php

namespace App\Classe;

use Mailjet\Client;
use Mailjet\Resources;

class Mail
{
    private string $apiKey    = '7ab6cb8cb0f264f6cf34b010b5de4ba4';
    private string $secretKey = 'b693ab6852121602f7aeb1fa948d7dd9';

    public function send($toEmail, $toName, $subject, $content)
    {
        $mj       = new Client($this->apiKey, $this->secretKey, true, ['version' => 'v3.1']);
        $body     = [
            'Messages' => [
                [
                    'From'             => [
                        'Email' => "auger.mickael37+mailjet@gmail.com",
                        'Name'  => "La Boutique Française",
                    ],
                    'To'               => [
                        [
                            'Email' => $toEmail,
                            'Name'  => $toName,
                        ],
                    ],
                    'TemplateID'       => 2200275,
                    'TemplateLanguage' => true,
                    'Subject'          => $subject,
                    'Variables'        => [
                        'content'             => $content,
                    ],
                ],
            ],
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);

        $response->success();
    }
}