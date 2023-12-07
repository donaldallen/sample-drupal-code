<?php

namespace Drupal\middleware_sync_cron\TaxonomyFields;

final class FieldLocal extends FieldBase
{
    /**
     * The field you want to map data from getData to
     *
     * @var string
     */
    protected string $field = 'field_local';

    /**
     * Find Drupal node IDs associated with the user's local data
     *
     * @param array $job A third-party-app job object
     *
     * @return array|null
     */
    protected function getData(array $job): ?array
    {
        try {
            $localId = str_pad($job['local'], 3, 0, STR_PAD_LEFT);

            $localNodeIds = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->getQuery()
                ->condition('vid', 'local')
                ->condition('name', $localId, 'STARTS_WITH')
                ->execute();

            if (is_array($localNodeIds) && sizeof($localNodeIds) > 0) {
                return $localNodeIds;
            }
        } catch (\Exception $e) {
            \Drupal::logger('middleware_sync_cron')->error($e->getMessage());
        }

        return null;
    }

}
