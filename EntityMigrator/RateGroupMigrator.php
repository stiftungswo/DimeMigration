<?php

namespace EntityMigrator;

use Main\HelperMethods;

class RateGroupMigrator extends BaseMigrator
{
    /**
     * Migrates the rate groups from the old to the new dime
     * Returns an array where the key is the id from the old dime, and the value is the id in the new dime
     * @return array
     */
    public function doMigration()
    {
        HelperMethods::printWithNewLine("\nMigrating rate groups ...");
        $reverseRateGroups = [];
        $oldRateGroups = $this->capsule->connection('oldDime')->table('rate_groups')->get();

        foreach ($oldRateGroups as $oldRateGroup) {
            $rateGroupNewId = $this->capsule->connection('newDime')->table('rate_groups')->insertGetId([
                'description' => $oldRateGroup->description,
                'name' => $oldRateGroup->name,
            ]);

            $reverseRateGroups[$oldRateGroup->id] = $rateGroupNewId;
        }

        return $reverseRateGroups;
    }
}
