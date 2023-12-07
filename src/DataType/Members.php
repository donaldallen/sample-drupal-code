<?php

namespace Drupal\middleware_sync_cron\DataType;

use Drupal\client_custom\Middleware;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\middleware_sync_cron\DrupalThirdPartyServiceFieldMapper;
use Drupal\middleware_sync_cron\Exceptions\DrupalThirdPartyServiceFieldMapperException;
use Drupal\middleware_sync_cron\SyncController;
use Drupal\middleware_sync_cron\SyncHelper;
use Drupal\user\Entity\User;

final class Members
{
    /**
     * Status indicating whether this data was synced correctly
     *
     * @var boolean
     */
    public bool $status = false;

    /**
     * Middleware library reference, shared instance between all DataType instances
     *
     * @var Drupal\client_custom\Middleware
     */
    private $middleware;

    /**
     * SQL offset value, for pagination
     *
     * @var integer
     */
    private int $offset = 0;

    /**
     * Creates a new instance of Locals and runs the ::execute method
     *
     * @param Drupal\client_custom\Middleware $middleware
     */
    public function __construct(Middleware $middleware, int $offset = 0)
    {
        $this->middleware = $middleware;
        $this->offset = $offset;

        $this->execute();
    }

    /**
     * Creates or updates chapters and locals if they are missing from Drupal.
     *
     * @return void
     */
    private function execute(): void
    {
        try {
            $members = SyncHelper::getMembers($this->offset, SyncController::MAX_RECORDS_PER_SET);

            if (!is_null($members) || sizeof($members) > 0) {
                foreach ($members as $member) {
                    if ($member instanceof User) {
                        \Drupal::logger('middleware_sync_cron')->info(
                            sprintf("UPDATING %d (uid %d)" . PHP_EOL, $member->get('name')->value, $member->id())
                        );
                        $this->update($member);
                    }
                }

                $this->status = true;
            }
        } catch (\Exception $e) {
            \Drupal::logger('middleware_sync_cron')->error($e->getMessage());
        }
    }

    /**
     * Updates member data with information stored in Third Party Service. Overwrites
     * Drupal.
     *
     * @param Drupal\user\Entity\User $member User object instance
     *
     * @return void
     */
    private function update(User $member): void
    {
        try {
            $middlewareUser = $this->middleware->getMemberData($member->get('name')->value);

            if (is_array($middlewareUser)) {
                $mapper = new DrupalThirdPartyServiceFieldMapper();
                $mapper->map($member, $middlewareUser['info'], $middlewareUser['jobs']);
            }
        } catch (DrupalThirdPartyServiceFieldMapperException $e) {
            \Drupal::logger('middleware_sync_cron')->warning($e->getMessage());
        } catch (\Exception $e) {
            // swallow as there will be many for invalid user IDs
        }
    }
}
