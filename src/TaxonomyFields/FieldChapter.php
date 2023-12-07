<?php

namespace Drupal\middleware_sync_cron\TaxonomyFields;

final class FieldChapter extends FieldBase
{
    /**
     * The field you want to map data from getData to
     *
     * @var string
     */
    protected string $field = 'field_chapter';

    /**
     * Find Drupal node IDs associated with the user's chapter data
     *
     * @param array $job A third-party-app job object
     *
     * @return array|null
     */
    protected function getData(array $job): ?array
    {
        try {
            $chapter = sprintf(
                "%s - %s",
                str_pad($job['chapter'], 3, 0, STR_PAD_LEFT),
                $job['chapter_name']
            );

            $chapterNodeIds = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->getQuery()
                ->condition('vid', 'chapter')
                ->condition('name', $chapter, '=')
                ->execute();

            if (is_array($chapterNodeIds) && sizeof($chapterNodeIds) > 0) {
                return $chapterNodeIds;
            }
        } catch (\Exception $e) {
            \Drupal::logger('middleware_sync_cron')->error($e->getMessage());
        }

        return null;
    }

}
