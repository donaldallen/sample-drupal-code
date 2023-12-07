<?php

namespace Drupal\middleware_sync_cron;

use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

final class SyncHelper
{
    /**
     * Creates the name (label) for a new local taxonomy term
     *
     * @param array $local Array of middleware data representing a local
     *
     * @return string
     */
    public static function localLabel(array $local): string
    {
        return sprintf(
            '%s - %s',
            str_pad($local['local_id'], 3, '0', STR_PAD_LEFT),
            $local['name']
        );
    }

    /**
     * Creates the name (label) for a new chapter taxonomy term
     *
     * @param array $chapter Array of middleware data representing a chapter
     *
     * @return string
     */
    public static function chapterLabel(array $chapter): string
    {
        return sprintf(
            '%s - %s',
            str_pad($chapter['id'], 3, '0', STR_PAD_LEFT),
            $chapter['name']
        );
    }

    /**
     * Find a local with a given name
     *
     * @param array $local
     *
     * @return Term|null
     */
    public static function findLocal(array $local): ?Term
    {
        $localId = str_pad($local['local_id'], 3, '0', STR_PAD_LEFT);

        $entity = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', 'local', '=')
            ->condition('name', $localId, 'STARTS_WITH')
            ->execute();

        if (!empty($entity)) {
            $value = array_values($entity)[0];

            return Term::load($value);
        }

        return null;
    }

    /**
     * Finds members whose usernames are numeric values greater than 1
     *
     * @param integer $page Start of the desired record set
     * @param integer $limit End of the desired record set
     *
     * @return array|null
     */
    public static function getMembers(int $page, int $limit): ?array
    {
        $query = \Drupal::entityQuery('user')
            // find  members, usernames start with numbers
            ->condition('name', '^\d', 'REGEXP')
            // ignore user id 1 (not a member)
            ->condition('name', '1', '>')
            ->sort('created', 'DESC')
            ->range($page, $limit);

        $ids = $query->execute();

        if (sizeof($ids) == 0) {
            return NULL;
        }

        return User::loadMultiple($ids);
    }
}
