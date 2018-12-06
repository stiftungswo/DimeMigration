<?php

namespace EntityMigrator;

use Main\HelperMethods;

class AddressMigrator extends BaseMigrator
{
    public function doMigration(\stdClass $oldAddress, $newCustomerId)
    {
        if (!empty($oldAddress->street) && !empty($oldAddress->plz) && !empty($oldAddress->city)) {
            HelperMethods::printWithNewLine("Creating or updating new address for new customer " . $newCustomerId);
            $this->capsule->connection('newDime')->table('addresses')->updateOrInsert([
                'city' => $oldAddress->city,
                'country' => $oldAddress->country,
                'customer_id' => $newCustomerId,
                'postcode' => $oldAddress->plz,
                'street' => $oldAddress->street,
                'supplement' => $oldAddress->supplement,
            ], [
                'city' => $oldAddress->city,
                'country' => $oldAddress->country,
                'customer_id' => $newCustomerId,
                'postcode' => $oldAddress->plz,
                'street' => $oldAddress->street,
                'supplement' => $oldAddress->supplement,
            ]);
        } else {
            HelperMethods::printWithNewLine("Missing Street, PLZ or city for address " . $oldAddress->id);
        }

        return $this->capsule->connection('newDime')->table('addresses')->where([
            'city' => $oldAddress->city,
            'country' => $oldAddress->country,
            'customer_id' => $newCustomerId,
            'postcode' => $oldAddress->plz,
            'street' => $oldAddress->street,
            'supplement' => $oldAddress->supplement,
        ])->first();
    }
}
