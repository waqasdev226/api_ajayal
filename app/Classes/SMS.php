<?php

namespace App\Classes;
use Illuminate\Support\Facades\Http;

class SMS
{
    static function sendSMS($phone, $text)
    {
       try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-KEY'    => 'eSITE-NDuo9TJGZTqxrS3AOjQwRvKXF3t2Lho80ligp3GS5sXcuYLVknctBUzlvu6PlEO7',
            ])->post('https://gateway.esite-lab.com/wa/api/v1/public/messages/channel/whatsapp/generic', [
                "messages" => [
                    [
                        "destinations" => [
                            [
                                "to" => $phone
                            ]
                        ],
                        "content" => [
                            "text" => $text
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json()
                ];
            }

            

            return [
                'success' => false,
                'error'   => $response->body()
            ];

        } catch (\Exception $e) {

        //    Log::error('WhatsApp Exception', [
          //      'message' => $e->getMessage()
           // ]);

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    static function checkBalance()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sms.esite-iq.com/api/balance',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'ESITE-API-KEY: '.env('SMS_KEY')
            ),
        ));

        $response = curl_exec($curl);

//        error_log(print_r($curl, true));
        error_log(print_r($response, true));

        curl_close($curl);

        return json_decode($response);

    }

}
