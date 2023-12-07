<?php

namespace Drupal\middleware_sync_cron;

use Drupal\client_custom\Middleware;
use Drupal\middleware_sync_cron\DataType\Locals;
use Drupal\middleware_sync_cron\DataType\Members;
use Symfony\Component\HttpFoundation\JsonResponse;

class SyncController
{
    /**
     * Maps response codes to pretty strings
     */
    private const SYNC_STATUS_MAP = [
        1 => 'success',
        0 => 'failure',
    ];

    /**
     * Number of records to process
     */
    public const MAX_RECORDS_PER_SET = 100000;

    /**
     * Entry point for both cron and route /middleware_sync_cron/sync/{which}
     *
     * @param string|null $which
     *
     * @return void
     */
    public static function cron(?string $which = 'full'): JsonResponse
    {
        $sync = new SyncController();
        $middleware = new Middleware();
        $output = ['which' => $which];

        switch ($which) {
            case 'full':
                $output['response'] = $sync->full($middleware);
                break;
            case '1':
                $output['response'] = $sync->members($middleware, 0);
                break;
            case '2':
                $output['response'] = $sync->members($middleware, self::MAX_RECORDS_PER_SET * 1);
                break;
            case '3':
                $output['response'] = $sync->members($middleware, self::MAX_RECORDS_PER_SET * 2);
                break;
            case '4':
                $output['response'] = $sync->members($middleware, self::MAX_RECORDS_PER_SET * 3);
                break;
            case 'locals':
                $output['response'] = $sync->locals($middleware);
                break;

            default:
                $output['response'] = "No task";
                break;
        }

        return new JsonResponse($output);
    }

    /**
     * Performs a full sync of all the different DataType objects
     *
     * @param Middleware $middleware Data library object
     *
     * @return array
     */
    private function full(Middleware $middleware): array
    {
        $data = [
            'locals' => new Locals($middleware),
            'members' => new Members($middleware)
        ];

        return array_map(
            function ($sync) {
                return self::SYNC_STATUS_MAP[$sync->status];
            },
            $data
        );
    }

    /**
     * Performs a member data sync
     *
     * @param Middleware $middleware Data library object
     * @param integer $offset
     * @return array
     */
    private function members(Middleware $middleware, int $offset = 0): array
    {
        $data = [
            'members' => new Members($middleware, $offset)
        ];

        return array_map(
            function ($sync) {
                return self::SYNC_STATUS_MAP[$sync->status];
            },
            $data
        );
    }

    /**
     * Performs a local/chapter data sync
     *
     * @param Middleware $middleware Data library object
     *
     * @return array
     */
    private function locals(Middleware $middleware): array
    {
        $data = [
            'locals' => new Locals($middleware),
        ];

        return array_map(
            function ($sync) {
                return self::SYNC_STATUS_MAP[$sync->status];
            },
            $data
        );
    }

}
