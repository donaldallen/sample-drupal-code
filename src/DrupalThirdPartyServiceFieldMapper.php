<?php

namespace Drupal\middleware_sync_cron;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\middleware_sync_cron\Exceptions\DrupalThirdPartyServiceFieldMapperException;
use Drupal\middleware_sync_cron\TaxonomyFields\FieldChapter;
use Drupal\middleware_sync_cron\TaxonomyFields\FieldLocal;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

final class DrupalThirdPartyServiceFieldMapper
{
    /**
     * Maps UW fields to Drupal data fields
     * [
     *  [DRUPAL_FIELD, UW_FIELD, TYPE],
     *  ...
     * ]
     */
    private const USER_FIELD_MAP = [
        ['mail', 'home_email', 'text'],
        // ['status', 'login_allowed', 'bool'], // TODO: this could be widely destructive
        ['field_first_name', 'first_name', 'text'],
        ['field_last_name', 'last_name', 'text'],
        ['field_address_1', 'postal_address_1', 'text'],
        ['field_address_2', 'postal_address_2', 'text'],
        ['field_province_code', 'prov_code', 'text'],
        ['field_province_name', 'prov_name', 'text'],
        ['field_city', 'city', 'text'],
        ['field_postal_code', 'postal_zip_code', 'text'],
        ['field_country', 'country', 'text'],
        ['field_home_phone', 'home_phone', 'text'],
        ['field_cell_phone', 'cell_phone', 'text'],
        ['field_gender', 'gender', 'text'],
        ['field_member_joined', 'member_joined_date', 'date'],
        ['field_local', null, 'taxonomy'], // performs a more complicated lookup
        ['field_chapter', null, 'taxonomy'], // performs a more complicated lookup
    ];

    /**
     * Maps Third Party Service (TPS) data to Drupal
     *
     * @param User $member Drupal user object
     * @param array $user UW data
     *
     * @return void
     */
    public function map(User $member, array $user, array $jobs): void
    {
        foreach (self::USER_FIELD_MAP as $map) {
            list($drupalField, $uwField, $type) = $map;

            // Taxonomy fields must be emptied of stored data before adding new items
            if ($type == 'taxonomy') {
                $member->$drupalField = [];
            }

            switch ($type) {
                case 'text':
                    $this->set($member, $drupalField, $user[$uwField]);
                    break;
                case 'date':
                    $this->setDate($member, $drupalField, $user[$uwField]);
                    break;
                case 'bool':
                    $this->setBool($member, $drupalField, $user[$uwField]);
                    break;
                case 'taxonomy':
                    $this->setTaxonomy($member, $drupalField, $jobs);
                    break;
                default:
                    throw new DrupalThirdPartyServiceFieldMapperException(
                        "Error importing member data from Middleware, unknown field type {$type}"
                    );
                    break;
            }
        }

        $member->save();
    }

    /**
     * Sets a value in the Drupal user profile, doesn't care about field type
     *
     * @param User $member User object passed by reference
     * @param string $memberKey Drupal member profile field key
     * @param mixed $value The value you wish to set
     *
     * @return void
     */
    private function set(User &$member, string $memberKey, $value): void
    {
        $member->get($memberKey)->value = $value;
    }

    /**
     * TODO: this saves data but doesn't update the date picker?
     * Set a field of the type Date (NOT compatible with "Date and time" config)
     *
     * @param User $member User object passed by reference
     * @param string $memberKey Drupal member profile field key
     * @param mixed $value The value you wish to set
     *
     * @return void
     */
    private function setDate(User &$member, string $memberKey, $value): void
    {
        $formatter = \Drupal::service('date.formatter');
        $uwDate = new DrupalDateTime($value, 'UTC');
        $memberFieldValue = $member->get($memberKey)->date;

        if (is_null($memberFieldValue)) {
            $date = $formatter->format(
                $uwDate->getTimestamp(),
                'custom',
                'Y-m-d'
            );

            $member->get($memberKey)->date = $date;
        }
    }

    /**
     * Sets a boolean field value, either on or off
     *
     * @param User $member User object passed by reference
     * @param string $memberKey Drupal member profile field key
     * @param mixed $value The value you wish to set
     *
     * @return void
     */
    private function setBool(User &$member, string $memberKey, $value): void
    {
        if (is_null($value) || $value == false) {
            $member->get($memberKey)->value = 0;
        } else {
            $member->get($memberKey)->value = 1;
        }
    }

    /**
     * Set all taxonomy fields
     *
     * @param User $member User object passed by reference
     * @param string $memberKey Drupal member profile field key
     * @param mixed $jobs The value you wish to set
     *
     * @return void
     */
    private function setTaxonomy(User &$member, string $memberKey, array $jobs = []): void
    {
        if (!empty($jobs)) {
            // associate each job to one of the fields which implement FieldBase
            foreach ($jobs as $job) {
                (new FieldChapter($member))->setIfMatches($memberKey, $job);
                (new FieldLocal($member))->setIfMatches($memberKey, $job);
            }
        }
    }

}
