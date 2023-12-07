<?php

namespace Drupal\middleware_sync_cron\DataType;

use Drupal\client_custom\Middleware;
use Drupal\middleware_sync_cron\SyncHelper;
use Drupal\taxonomy\Entity\Term;

final class Locals
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
     * Creates a new instance of Locals and runs the ::execute method
     *
     * @param Drupal\client_custom\Middleware $middleware
     */
    public function __construct(Middleware $middleware)
    {
        $this->middleware = $middleware;

        $this->execute();
    }

    /**
     * Creates or updates chapters and locals if they are missing from Drupal
     *
     * @return void
     */
    private function execute(): void
    {
        $locals = $this->middleware->getLocals() ?? [];

        if (sizeof($locals) > 0) {
            for ($i = 0; $i < sizeof($locals); $i++) {
                $local = SyncHelper::findLocal($locals[$i]);
                $code = $locals[$i]['code'];

                if (empty($local)) {
                    $this->create($locals[$i], $code);
                } else {
                    $this->update($local, $code);
                }

                $this->status = true;
            }
        }
    }

    /**
     * Create new local from information received from Middleware
     *
     * @param array $local Local data from Middleware
     * @param int $code Local code
     *
     * @return void
     */
    private function create(array $local, int $code): void
    {
        $entity = Term::create(
            [
                'vid' => 'local',
                'name' => SyncHelper::localLabel($local),
                'field_chapters' => []
            ]
        );
        $entity->save();

        $this->update($entity, $code);
    }

    /**
     * Updates an existing local with new chapters.  Currently it only adds data,
     * it does not change existing information.
     *
     * @param Drupal\taxonomy\Entity\Term $local Term instance for a given local
     * @param integer $code Local code
     *
     * @return void
     */
    private function update(Term $local, int $code): void
    {
        $chapters = $this->middleware->getChapters($code) ?? [];
        $child_chapters = [];

        if (sizeof($chapters) > 0) {
            for ($i = 0; $i < sizeof($chapters); $i++) {
                $chapter = Term::create(
                    [
                        'vid' => 'chapter',
                        'name' => SyncHelper::chapterLabel($chapters[$i])
                    ]
                );
                $chapter->save();

                $child_chapters[] = $chapter->id();
            }
        }

        if (sizeof($child_chapters) > 0) {
            $local->set('field_chapters', $child_chapters);
        }

        $local->save();
    }

}
