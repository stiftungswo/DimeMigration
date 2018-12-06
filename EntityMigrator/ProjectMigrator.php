<?php

namespace EntityMigrator;

use Main\HelperMethods;

class ProjectMigrator extends BaseMigrator
{
    public function doMigration(array $reverseCustomers, array $reverseEmployees, array $reverseOffers, array $reverseProjectCategories, array $reverseRateGroups, array $reverseServices)
    {
        $addressMigrator = new AddressMigrator();
        $rateUnitMigrator = new RateUnitMigrator();

        $oldProjects = $this->capsule->connection('oldDime')->table('projects')->get();

        foreach ($oldProjects as $oldProject) {
            HelperMethods::printWithNewLine("\nMigrating project " . $oldProject->id);
            // find offer related to the project and take its address
            $eventualOffer = $this->capsule->connection('oldDime')->table('offers')->where([
                'project_id' => $oldProject->id
            ])->first();

            if (is_null($eventualOffer)) {
                // if project does not have any offers, search through the old customer for an address
                $oldCustomer = $this->capsule->connection('oldDime')->table('customers')->where([
                    'id' => $oldProject->customer_id
                ])->first();
                $oldAddressId = $oldCustomer ? $oldCustomer->address_id : null;
            } else {
                $oldAddressId = $eventualOffer->address_id;
            }

            if (is_null($oldAddressId)) {
                $newAddress = null;
            } else {
                $oldAddress = $this->capsule->connection('oldDime')->table('address')->where([
                    'id' => $oldAddressId
                ])->first();
                $newAddress = $addressMigrator->doMigration($oldAddress, empty($reverseCustomers[$oldProject->customer_id]['company']) ? $reverseCustomers[$oldProject->customer_id]['person'] : $reverseCustomers[$oldProject->customer_id]['company']);
            }

            $newProjectId = $this->capsule->connection('newDime')->table('projects')->insertGetId([
                'accountant_id' => $oldProject->accountant_id ? $reverseEmployees[$oldProject->accountant_id] : $oldProject->accountant_id,
                'address_id' => is_null($newAddress) ? null : $newAddress->id,
                'archived' => $oldProject->archived == 1,
                'category_id' => $oldProject->project_category_id ? $reverseProjectCategories[$oldProject->project_category_id] : null,
                'chargeable' => $oldProject->chargeable == 1,
                'created_at' => $oldProject->created_at,
                'created_by' => $oldProject->user_id ? $reverseEmployees[$oldProject->user_id] : null,
                'customer_id' => $reverseCustomers[$oldProject->customer_id]['person'],
                'deadline' => $oldProject->deadline,
                'description' => $oldProject->description,
                'id' => $oldProject->id,
                'fixed_price' => HelperMethods::examineMoneyValue($oldProject->fixed_price),
                'name' => $oldProject->name,
                'offer_id' => is_null($eventualOffer) ? null : $reverseOffers[$eventualOffer->id],
                'rate_group_id' => $reverseRateGroups[$oldProject->rate_group_id],
                'updated_at' => $oldProject->updated_at,
                'vacation_project' => $oldProject->name == 'SWO: Ferien'
            ]);

            // migrate its positions
            $oldPositionsOfProject = $this->capsule->connection('oldDime')->table('activities')->where([
                'project_id' => $oldProject->id
            ])->get();

            HelperMethods::printWithNewLine("\nMigrating positions for project " . $oldProject->id);
            foreach ($oldPositionsOfProject as $oldProjectPosition) {
                $this->capsule->connection('newDime')->table('project_positions')->insert([
                    'created_at' => $oldProjectPosition->created_at,
                    // 'created_by' => $oldProjectPosition->user_id ? $reverseEmployees[$oldProjectPosition->user_id] : null,
                    'deleted_at' => $oldProjectPosition->deleted_at,
                    'description' => $oldProjectPosition->description,
                    'price_per_rate' => HelperMethods::examineMoneyValue($oldProjectPosition->rate_value),
                    'project_id' => $newProjectId,
                    'rate_unit_id' => $rateUnitMigrator->doMigration($oldProjectPosition->rateUnitType_id, $oldProjectPosition->rate_unit),
                    'service_id' => $reverseServices[$oldProjectPosition->service_id],
                    'updated_at' => $oldProjectPosition->updated_at,
                    'vat' => $oldProjectPosition->vat ?: 0
                ]);
            }
        }
    }
}
