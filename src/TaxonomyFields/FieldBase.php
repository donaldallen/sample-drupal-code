<?php

namespace Drupal\middleware_sync_cron\TaxonomyFields;

use Drupal\user\Entity\User;

class FieldBase
{
    /**
     * The field you want to map data from getData to
     *
     * @var string
     */
    protected string $field;

    /**
     * Reference to the User object instance
     *
     * @var Drupal\user\Entity\User
     */
    protected $member;

    /**
     * Create a new instance of this taxonomy field
     *
     * @param Drupal\user\Entity\User $member Reference to the member object
     */
    public function __construct(User &$member)
    {
        $this->member = $member;
    }

    /**
     * Set a field's value if it matches a known child class's $field param
     *
     * @param string $fieldName Defined in DrupalThirdPartyServiceFieldMapper::USER_FIELD_MAP
     * @param array $job Individual job data
     *
     * @return void
     */
    public function setIfMatches(string $fieldName, array $job): void
    {
        if ($this->field == $fieldName) {
            $this->setField($job);
        }
    }

    /**
     * Sets the field value by calling a child class's ::getData method
     *
     * @param array $job Individual job data
     *
     * @return void
     */
    private function setField(array $job = []): void
    {
        if ($ids = $this->getData($job)) {
            $this->member->{$this->field} = array_unique($ids);
        }
    }

}
