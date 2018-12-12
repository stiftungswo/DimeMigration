<?php

namespace EntityMigrator;

use DateTime;

class RateUnitMigrator extends BaseMigrator
{
    /**
     * Check if there is already a rate unit with the same combination of effort and billing unit
     * If not, creates a new Rate Unit
     * returns in either way an ID of a matching rate unit
     * @param string $effort_unit
     * @param string $billing_unit
     * @return int
     * @throws \Exception
     */
    public function doMigration($effort_unit, $billing_unit, $archived = false)
    {
        $matchingNewRateUnits = $this->capsule->connection('newDime')->table('rate_units')->where([
            ['billing_unit', "=", $billing_unit],
            ['effort_unit', '=', $effort_unit]
        ])->get();

        if ($matchingNewRateUnits->isEmpty()) {
            // find old rate unit
            $oldRateUnit = $this->capsule->connection('oldDime')->table('rateunittypes')
                ->where('id', '=', $effort_unit)->first();

            // create new rate unit
            $newRateUnitId = $this->capsule->connection('newDime')->table('rate_units')->insertGetId([
                'archived' => $archived,
                'billing_unit' => $billing_unit,
                'created_at' => new DateTime(),
                'effort_unit' => $effort_unit,
                'factor' => $oldRateUnit ? $oldRateUnit->factor == 1 ? 1 : $oldRateUnit->factor / 60 : 1,
                'is_time' => $oldRateUnit ? $oldRateUnit->factor != 1 : false,
                'name' => $oldRateUnit ? $oldRateUnit->name : $billing_unit,
                'updated_at' => new DateTime(),
            ]);
        } else {
            $newRateUnitId = $matchingNewRateUnits->first()->id;
        }

        return $newRateUnitId;
    }
}
