<?php

namespace EntityMigrator;

use Main\HelperMethods;

class ProjectPositionWithEffortsMigrator extends BaseMigrator
{
    public function doMigrate(array $reverseEmployees, array $reverseServices)
    {
        // because we need to transfer the old timeslices, we need the old transform values
        // those get exposed during the migration of the project positions
        HelperMethods::printWithNewLine("\nMigrating project positions");
        $rateUnitMigrator = new RateUnitMigrator();
        $reverseProjectPosition = [];
        $oldProjectPositions = $this->capsule->connection('oldDime')->table('activities')->get();

        foreach ($oldProjectPositions as $oldProjectPosition) {
            // we first need to migrate the rate unit to get the factor
            $newRateUnitId = $rateUnitMigrator->doMigration($oldProjectPosition->rateUnitType_id, $oldProjectPosition->rate_unit);
            $newRateUnit = $this->capsule->connection('newDime')->table('rate_units')->find($newRateUnitId);

            $newProjectPositionId = $this->capsule->connection('newDime')->table('project_positions')->insertGetId([
                'created_at' => $oldProjectPosition->created_at,
                // 'created_by' => $oldProjectPosition->user_id ? $reverseEmployees[$oldProjectPosition->user_id] : null,
                'deleted_at' => $oldProjectPosition->deleted_at,
                'description' => $oldProjectPosition->description,
                'price_per_rate' => HelperMethods::examineMoneyValue($oldProjectPosition->rate_value),
                'project_id' => $oldProjectPosition->project_id,
                'rate_unit_id' => $newRateUnitId,
                'service_id' => $reverseServices[$oldProjectPosition->service_id],
                'updated_at' => $oldProjectPosition->updated_at,
                'vat' => $oldProjectPosition->vat ?: 0
            ]);

            $reverseProjectPosition[$oldProjectPosition->id] = $newProjectPositionId;

            // now we can migrate the associated effort
            // if the factor of the new rate unit is not 1, we need to divide the value of the effort with 60
            // thats the same things that's happening with the old rate units
            HelperMethods::printWithNewLine("Migrating project efforts for position " . $oldProjectPosition->id);
            $oldTimeslicesOfPosition = $this->capsule->connection('oldDime')->table('timeslices')->where(
                'activity_id',
                '=',
                $oldProjectPosition->id
            )->get();

            foreach ($oldTimeslicesOfPosition as $oldTimeslice) {
                $this->capsule->connection('newDime')->table('project_efforts')->insert([
                    'created_at' => $oldTimeslice->created_at,
                    'created_by' => $oldTimeslice->user_id ? $reverseEmployees[$oldTimeslice->user_id] : null,
                    'deleted_at' => $oldTimeslice->deleted_at,
                    'date' => $oldTimeslice->started_at,
                    'employee_id' => is_null($oldTimeslice->employee_id) ? : $reverseEmployees[$oldTimeslice->employee_id],
                    'position_id' => $newProjectPositionId,
                    'value' => $newRateUnit->factor == 1 ? $oldTimeslice->value : $oldTimeslice->value / 60,
                    'updated_at' => $oldTimeslice->updated_at,
                ]);
            }
        }

        return $reverseProjectPosition;
    }
}
