<?php

namespace App\Http\Traits;

trait UniqueUndeletedTrait
{
    /**
     * Prepare a unique_ids rule, adding a model identifier if required.
     *
     * @param  array  $parameters
     * @param  string  $field
     * @return string
     */
    protected function prepareUniqueUndeletedRule($parameters, $field)
    {
        // Only perform a replacement if the model has been persisted.
        if ($this->exists) {
            return 'unique_undeleted:'.$this->table.','.$this->getKey();
        }

        return 'unique_undeleted:'.$this->table.',0';
    }

    /**
     * Prepare a `unique_undeleted_in_scope` rule.
     *
     * Model declares:  `'name' => 'unique_undeleted_in_scope:parent_id,company_id'`
     * We rewrite to:   `'name' => 'unique_undeleted_in_scope:locations,42,parent_id,company_id'`
     *
     * The rule closure in ValidationServiceProvider reads the current
     * request/model value of each scope column via `$validator->getData()`,
     * so a new location named "Rack 1" under DC1 is compared only against
     * other locations whose parent_id (and company_id) match, not
     * globally as `unique_undeleted` does.
     *
     * @param  array  $parameters  the scope column names declared on the model
     * @param  string  $field  the attribute being validated
     */
    protected function prepareUniqueUndeletedInScopeRule($parameters, $field)
    {
        $id = $this->exists ? $this->getKey() : 0;

        return 'unique_undeleted_in_scope:'.$this->table.','.$id.','.implode(',', $parameters);
    }
}
