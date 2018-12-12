<?php

namespace EntityMigrator;

use Main\HelperMethods;

class CustomerMigrator extends BaseMigrator
{
    /**
     * Migrates the customers from the old to the new dime
     * Because company and people are now different entities, return an array with both company and person id
     * This is relevant for the later address migration
     * @param array $reverseEmployees
     * @param array $reverseRateGroups
     * @return array
     */
    public function doMigration(array $reverseEmployees, array $reverseRateGroups)
    {
        HelperMethods::printWithNewLine("\nMigrating customers ...");
        $reverseCustomers = [];
        $addressMigrator = new AddressMigrator();
        $oldCustomers = $this->capsule->connection('oldDime')->table('customers')->get();

        foreach ($oldCustomers as $oldCustomer) {
            // if the existing customer has the company field out, check if we have this company already
            // if not, create it, otherwise use the existing one
            if (!empty($oldCustomer->company)) {
                $potentialAlreadyMigratedCompany = $this->capsule->connection('newDime')->table('customers')->where([
                    ['name', '=', $oldCustomer->company]
                ])->first();

                if (is_null($potentialAlreadyMigratedCompany)) {
                    HelperMethods::printWithNewLine("Creating a new company " . $oldCustomer->company);
                    $newCompanyId = $this->capsule->connection('newDime')->table('customers')->insertGetId([
                        'name' => $oldCustomer->company,
                        'hidden' => $oldCustomer->system_customer != 1,
                        'type' => 'company',
                        'rate_group_id' => $reverseRateGroups[$oldCustomer->rate_group_id]
                    ]);
                } else {
                    $newCompanyId = $potentialAlreadyMigratedCompany->id;
                }

                $reverseCustomers[$oldCustomer->id]['company'] = $newCompanyId;
            }

            // dont create a person if fullname field of oldCustomer is empty
            // then it is probably just a company
            if (!empty($oldCustomer->fullname)) {
                // create new person
                $partForName = explode(' ', $oldCustomer->fullname ?: $oldCustomer->name);
                $lastName = array_pop($partForName);
                $firstName = array(implode(' ', $partForName), $lastName)[0];

                HelperMethods::printWithNewLine("Creating a new person " . $firstName . ' ' . $lastName);
                $newPersonId = $this->capsule->connection('newDime')->table('customers')->insertGetId([
                    'company_id' => $oldCustomer->company ? $newCompanyId : null,
                    'comment' => $oldCustomer->comment,
                    'created_at' => $oldCustomer->created_at,
                    'department' => $oldCustomer->department,
                    'email' => $oldCustomer->email,
                    'hidden' => $oldCustomer->system_customer != 1,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'rate_group_id' => $oldCustomer->rate_group_id,
                    'type' => 'person',
                    'salutation' => $oldCustomer->salutation,
                    'updated_at' => $oldCustomer->updated_at,
                    'updated_by' => is_null($oldCustomer->user_id) ? null : $reverseEmployees[$oldCustomer->user_id],
                ]);
                $reverseCustomers[$oldCustomer->id]['person'] = $newPersonId;
            }

            // create new phone if set on old customer
            if (!empty($oldCustomer->phone)) {
                HelperMethods::printWithNewLine("Creating new phone for " . $oldCustomer->name);
                $this->capsule->connection('newDime')->table('phones')->insert([
                    'category' => $oldCustomer->company ? 2 : 3,
                    'customer_id' => $newPersonId ?: $newCompanyId,
                    'number' => $oldCustomer->phone,
                ]);
            }

            // create new mobile number if set on old customer
            if (!empty($oldCustomer->mobilephone)) {
                HelperMethods::printWithNewLine("Creating new mobile phone for " . $oldCustomer->name);
                $this->capsule->connection('newDime')->table('phones')->insert([
                    'category' => 4,
                    'customer_id' => $newPersonId ?: $newCompanyId,
                    'number' => $oldCustomer->mobilephone,
                ]);
            }

            $addressOfOldCustomer = $this->capsule->connection('oldDime')->table('address')
                ->where('id', '=', $oldCustomer->address_id)->first();

            if (!is_null($addressOfOldCustomer)) {
                $addressMigrator->doMigration($addressOfOldCustomer, empty($reverseCustomers[$oldCustomer->id]['company']) ? $reverseCustomers[$oldCustomer->id]['person'] : $reverseCustomers[$oldCustomer->id]['company']);
            }
        }

        return $reverseCustomers;
    }
}
