<?php

namespace EntityMigrator;

use Main\HelperMethods;

class ServiceWithRatesMigrator extends BaseMigrator
{
    /**
     * Copies the old services and rates to the new services and service rates
     * @param array $reverseEmployees
     * @param array $reverseRateGroups
     * @return array
     * @throws \Exception
     */
    public function doMigration(array $reverseEmployees, array $reverseRateGroups)
    {
        $rateUnitMigrator = new RateUnitMigrator();
        $reverseServices = [];
        
        HelperMethods::printWithNewLine("\nMigrating services ...");
        $oldServices = $this->capsule->connection('oldDime')->table('services')->get();
        foreach ($oldServices as $oldService) {
            HelperMethods::printWithNewLine("Migrating Service " . $oldService->name);

            $newServiceId = $this->capsule->connection('newDime')->table('services')->insertGetId([
                'archived' => $oldService->archived == 1,
                'created_at' => $oldService->created_at,
                'created_by' => $reverseEmployees[$oldService->user_id],
                'deleted_at' => $oldService->deleted_at,
                'description' => $oldService->description,
                'name' => $oldService->name,
                'vat' => $oldService->vat,
                'updated_at' => $oldService->updated_at,
            ]);

            $reverseServices[$oldService->id] = $newServiceId;

            $ratesOfService = $this->capsule->connection('oldDime')->table('rates')
                ->where('service_id', '=', $oldService->id)->get();

            foreach ($ratesOfService as $rateOfService) {
                $this->capsule->connection('newDime')->table('service_rates')->insert([
                    'created_at' => $rateOfService->created_at,
                    'rate_group_id' => $reverseRateGroups[$rateOfService->rate_group_id],
                    'rate_unit_id' => $rateUnitMigrator->doMigration($rateOfService->rateUnitType_id, $rateOfService->rate_unit),
                    'service_id' => $newServiceId,
                    'updated_at' => $rateOfService->updated_at,
                    'value' => HelperMethods::examineMoneyValue($rateOfService->rate_value)
                ]);
            }
        }

        return $reverseServices;
    }
}
