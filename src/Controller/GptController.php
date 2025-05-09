<?php

namespace Codebuster\GptBundle\Controller;

use Contao\Config;
use Exception;
use Contao\BackendUser;
use Contao\System;
use Contao\Input;
use Codebuster\GptBundle\Classes\GptClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use http\Client;

/**
 * @Route("/_gpt", name=GptController::class, defaults={"_scope" = "backend", "_token_check" = true})
 * @ServiceTag("controller.service_arguments")
 */
class GptController
{
    public function __invoke(Request $request): Response
    {

        $container = System::getContainer();
        $blnBackend = $container->get('contao.security.token_checker')->hasBackendUser();
        $strContent = "";
        if ($blnBackend === false) {
            return new Response('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        $strMode = Input::get('mode');

        if(Input::get('id')) {
            $intPage = Input::get('id');
            $strTable = Input::get('table');
            $strContent = GptClass::getContent($strTable, $intPage);
        }



        $response = $this->doRequest($strMode,$strContent);

        return new Response($response,Response::HTTP_OK);
    }

    private function doRequest(string $mode, string $content): string {

        $strReturn = "";
        $arrReturn = [];
        $strPrompt = "";
        $arrPost = [];
        $token = Config::get('gpt_token');
        $endpoint = Config::get('gpt_endpoint');


        if($token) {

            if($mode == 'title') {
                $strPrompt = Config::get('gpt_title_prompt');
            } else if($mode == 'description') {
                $strPrompt = Config::get('gpt_desc_prompt');
            } else if($mode == 'tinymce') {
                $strPrompt = Input::get('prompt');
            }

            if(!$strPrompt){
                return 'Please define prompt in OpenAI settings';
            }

            if($endpoint == 'Complete') {
                $strUrl = 'https://api.openai.com/v1/completions';
                $arrPost = [
                    "model" => Config::get('gpt_model_complete'),
                    "prompt" => $strPrompt." ".$content,
                    "max_tokens" => Config::get('gpt_max_tokens'),
                    "temperature" => Config::get('gpt_temp')
                ];
            } else if($endpoint == 'Chat') {
                $strUrl = 'https://api.openai.com/v1/chat/completions';
                $arrPost = [
                    "model" => Config::get('gpt_model_chat'),
                    "messages" => [
                        [
                            "role" => "system",
                            "content" => $strPrompt . " " . $content,
                        ]
                    ],
                    "max_tokens" => Config::get('gpt_max_tokens'),
                    "temperature" => Config::get('gpt_temp')
                ];
            }


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $strUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($arrPost),
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "Authorization: Bearer $token"
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $content = json_decode($response);

            
            if(isset($content->error) && $content->error->type === "insufficient_quota") {
                throw new Exception("Insufficient Quota.");
            }

            if(isset($content->error) && $content->error) {
                $arrReturn = [
                    "content" => $content->error->message,
                    "success" => false
                ];
            } else {
                if($endpoint == 'Complete') {
                    $strReturn = $content->choices[0]->text;
                    if(!$strReturn){
                        return "Something went wrong, pls try again...";
                    }

                } else if($endpoint == 'Chat') {
                    $strReturn = $content->choices[0]->message->content;
                }
                
                $strReturn = ltrim($strReturn, ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~'); 

                $arrReturn = [
                    "content" => str_replace(['"','*'],'',trim(preg_replace('/\s+/', ' ', $strReturn))),
                    "success" => true
                ];
            }

            $strReturn = json_encode($arrReturn);
        }

        



        return $strReturn;
    }


}