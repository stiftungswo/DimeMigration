<?php

namespace EntityMigrator;

use Main\HelperMethods;

class CostgroupMigrator extends BaseMigrator
{
    public function doMigrate()
    {
        $reverseCostgroups = [];

        $oldCostgroups = $this->capsule->connection('oldDime')->table('costgroups')->get();
        foreach ($oldCostgroups as $oldCostgroup) {
            HelperMethods::printWithNewLine("\nMigrating project " . $oldCostgroup->number);

            $this->capsule->connection('newDime')->table('costgroups')->insert([
                'created_at' => $oldCostgroup->created_at,
                'number' => $oldCostgroup->number,
                'name' => $oldCostgroup->description,
                'updated_at' => $oldCostgroup->updated_at,
            ]);

            $reverseCostgroups[$oldCostgroup->id] = $oldCostgroup->number;
        }

        return $reverseCostgroups;
    }
}
