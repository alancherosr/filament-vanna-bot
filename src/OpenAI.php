<?php

namespace Alancherosr\FilamentVannaBot;

use Exception;

final class OpenAI{

    /**
     * @throws Exception
     */
    public static function client()
    {
        $openai_key = config('filament-vanna-bot.openai.api_key');
        if(!$openai_key){
            return throw new Exception("API_KEY Missing!");
        }
        $proxy = config('filament-vanna-bot.proxy');

        $openai = new \Orhanerday\OpenAi\OpenAi($openai_key);
        if($proxy){
            $openai->setProxy($proxy);
        }

        return $openai;

    }

}
