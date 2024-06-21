<?php

namespace eDiasoft\Midjourney\Commands;

use eDiasoft\Midjourney\Exceptions\MidjourneyException;
use eDiasoft\Midjourney\Resources\Discord;
use eDiasoft\Midjourney\Resources\Midjourney;

class Shorten extends BaseCommand
{
    private int $maxRetries = 100;
    private int $intervalSeconds = 10;
    protected array $payload;

    private const ID = '1121575372539039774';
    private const VERSION = '1247736572414001223';

    public function payload(): array
    {

        $this->payload = array_merge($this->payload, array(
            'data'              => [
                'version'           => self::VERSION,
                'id'                => self::ID,
                'name'              => 'shorten',
                'type'              => 1,
                'options'           => array([
                    'type'      =>  3,
                    'name'      => 'prompt',
                    'value'     => $this->prompt,
                ]),
                'application_command'   =>  [
                    'id'                            =>  self::ID,
                    'application_id'                =>  Midjourney::APPLICATION_ID,
                    'version'                       =>  self::VERSION,
                    'default_member_permissions'    =>  null,
                    'type'                          =>  1,
                    'name'                          =>  'shorten',
                    'description'                   =>  'Analyzes and shortens a prompt.',
                    'dm_permission'                 =>  true,
                    'integration_types'             =>  [0, 1],
                    'nsfw'                          =>  false,
                    'attachments'                   => [],
                    'contexts'                      => [0, 1, 2],
                    'global_popularity_rank'        => 1,
                    'description_localized'         => 'Analyzes and shortens a prompt.',
                    'name_localized'                => 'describe',
                ]
            ]
        ));

        return parent::payload();
    }

    public function relax()
    {
        $this->arguments[] = "--relax";

        $this->intervalSeconds = 60;

        return $this;
    }

    public function turbo()
    {
        $this->arguments[] = "--turbo";

        $this->intervalSeconds = 15;

        return $this;
    }


    public function setMaxRetries(int $maxRetries)
    {
        $this->maxRetries = $maxRetries;

        return $this;
    }

    public function send()
    {
        
        parent::send();

        return $this->retrieveGeneratedImage();
    }


    private function retrieveGeneratedImage($tries = 0)
    {
        if($tries <= $this->maxRetries)
        {
            sleep($this->intervalSeconds);

            $response = $this->client->get(Discord::CHANNELS_URL . '/' . $this->config->channelId() . '/messages');
            $re = '/(.*)(\[(.*)\])(.*)/m';

            foreach($response->body() as $message)
            {
                preg_match($re, $message['content'], $matches);

                if(isset($matches[3]) && $matches[3] == $this->interactionId && !empty($message['attachments']) && !str_contains($message['attachments'][0]['filename'], 'grid'))
                {
                    return $message;
                }
            }

            return $this->retrieveGeneratedImage($tries + 1);
        }

        throw new MidjourneyException('Max tries exceeded, increase the max try attempt ($midjourney->imagine(*)->setMaxRetries(30)) or check your discord what went wrong.');
    }
}
