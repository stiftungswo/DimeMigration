<?php

namespace EntityMigrator;

use Main\HelperMethods;

class ProjectMigrator extends BaseMigrator
{
    public function doMigration(array $reverseCustomers, array $reverseEmployees, array $reverseProjectCategories, array $reverseRateGroups, array $reverseServices)
    {
        $addressMigrator = new AddressMigrator();

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

            $this->capsule->connection('newDime')->table('projects')->insert([
                'accountant_id' => $oldProject->accountant_id ? $reverseEmployees[$oldProject->accountant_id] : $oldProject->accountant_id,
                'address_id' => is_null($newAddress) ? null : $newAddress->id,
                'archived' => $oldProject->archived == 1,
                'category_id' => $oldProject->project_category_id ? $reverseProjectCategories[$oldProject->project_category_id] : null,
                'chargeable' => $oldProject->chargeable == 1,
                'created_at' => $oldProject->created_at,
                'created_by' => $oldProject->user_id ? $reverseEmployees[$oldProject->user_id] : null,
                'customer_id' => $reverseCustomers[$oldProject->customer_id]['person'] ?: $reverseCustomers[$oldProject->customer_id]['company'],
                'deadline' => $oldProject->deadline,
                'description' => $oldProject->description,
                'id' => $oldProject->id,
                'fixed_price' => HelperMethods::examineMoneyValue($oldProject->fixed_price, true),
                'name' => $oldProject->name,
                'offer_id' => $eventualOffer ? $eventualOffer->id : null,
                'rate_group_id' => $reverseRateGroups[$oldProject->rate_group_id],
                'updated_at' => $oldProject->updated_at,
                'vacation_project' => $oldProject->name == 'SWO: Ferien'
            ]);
        }
    }
}
