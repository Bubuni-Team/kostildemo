<?php /** @noinspection PhpUnused */
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 20.06.2021
 * Time: 15:15
 * Made with <3 by West from Bubuni Team
 */

namespace App\Controller;


class Api extends AbstractController
{
    public function actionConfig(): string
    {
        return $this->json([
            'chunkSize' => $this->getChunkSize()
        ]);
    }

    protected function getChunkSize()
    {
        $config = $this->app()->config()['system']['chunkSize'] ?? 'auto';
        if (is_int($config))
        {
            return $config;
        }

        if ($config === 'auto')
        {
            // This method is very complicant. We're try get all possible
            // limitations and select the strongest (lowest) value. This
            // should work the best thing.
            $values = [];

            // We're try detect CloudFlare.
            // Very dumbest method, but should work.
            foreach (['CF_REQUEST_ID', 'CF_CONNECTING_IP', 'CF_VISITOR', 'CF_RAY', 'CF_IPCOUNTRY'] as $header)
            {
                if (array_key_exists('HTTP_' . $header, $_SERVER))
                {
                    $values[] = 100000000;
                    break;
                }
            }

            // Get a ini parameters and push to array too.
            $values[] = $this->sizeToBytes(ini_get('post_max_size'));
            $values[] = $this->sizeToBytes(ini_get('upload_max_filesize'));

            // Then get the lowest value.
            return min($values);
        }

        return $this->sizeToBytes($config);
    }

    /** @noinspection PhpMissingBreakStatementInspection */
    protected function sizeToBytes(string $size): int
    {
        $bytes = intval($size);
        switch (strtolower(substr($size, -1)))
        {
            case 'g':
                $bytes *= 1024;
            // fall through

            case 'm':
                $bytes *= 1024;
            // fall through

            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }
}