<?php

namespace EntityMigrator;

use Main\HelperMethods;

class OfferMigrator extends BaseMigrator
{
    public function doMigration(array $reverseCustomers, array $reverseEmployees, array $reverseRateGroups, array $reverseServices)
    {
        $oldOffers = $this->capsule->connection('oldDime')->table('offers')->get();
        $addressMigrator = new AddressMigrator();
        $rateUnitMigrator = new RateUnitMigrator();

        foreach ($oldOffers as $oldOffer) {
            HelperMethods::printWithNewLine("\nMigrating offer " . $oldOffer->id);
            $oldAddressOfOffer = $this->capsule->connection('oldDime')->table('address')->where('id', '=', $oldOffer->address_id)->first();
            $newAddress = $addressMigrator->doMigration($oldAddressOfOffer, empty($reverseCustomers[$oldOffer->customer_id]['company']) ? $reverseCustomers[$oldOffer->customer_id]['person'] : $reverseCustomers[$oldOffer->customer_id]['company']);

            $newOfferId = $this->capsule->connection('newDime')->table('offers')->insertGetId([
                'accountant_id' => $reverseEmployees[$oldOffer->accountant_id],
                'address_id' => empty($newAddress) ? null : $newAddress->id,
                'created_at' => $oldOffer->created_at,
                'created_by' => is_null($oldOffer->user_id) ? null : $reverseEmployees[$oldOffer->user_id],
                'customer_id' => $reverseCustomers[$oldOffer->customer_id]['person'],
                'description' => $oldOffer->description,
                'id' => $oldOffer->id,
                'fixed_price' => HelperMethods::examineMoneyValue($oldOffer->fixed_price, true),
                'name' => $oldOffer->name,
                'rate_group_id' => $reverseRateGroups[$oldOffer->rate_group_id],
                'short_description' => $oldOffer->short_description,
                'status' => $oldOffer->status_id,
                'updated_at' => $oldOffer->updated_at
            ]);

            $oldPositionsOfOffer = $this->capsule->connection('oldDime')->table('offer_positions')->where('offer_id', '=', $oldOffer->id)->get();
            HelperMethods::printWithNewLine("Migrating positions for " . $oldOffer->id);
            foreach ($oldPositionsOfOffer as $oldOfferPosition) {
                $this->capsule->connection('newDime')->table('offer_positions')->insert([
                    'amount' => $oldOfferPosition->amount,
                    'created_at' => $oldOfferPosition->created_at,
                    'offer_id' => $newOfferId,
                    'order' => is_null($oldOfferPosition->order_no) ? 0 : $oldOfferPosition->order_no,
                    'price_per_rate' =>HelperMethods::examineMoneyValue($oldOfferPosition->rate_value),
                    'rate_unit_id' => $rateUnitMigrator->doMigration($oldOfferPosition->rateUnitType_id, $oldOfferPosition->rate_unit, true),
                    'service_id' => $reverseServices[$oldOfferPosition->service_id],
                    'updated_at' => $oldOfferPosition->updated_at,
                    'vat' => $oldOfferPosition->vat
                ]);
            }

            $oldDiscountsOfOffer = $this->capsule->connection('oldDime')->table('offer_discounts')->where('offer_id', '=', $oldOffer->id)->get();
            HelperMethods::printWithNewLine("Migrating discounts for " . $oldOffer->id);
            foreach ($oldDiscountsOfOffer as $oldOfferDiscount) {
                $this->capsule->connection('newDime')->table('offer_discounts')->insert([
                    'created_at' => $oldOfferDiscount->created_at,
                    'name' => $oldOfferDiscount->name,
                    'offer_id' => $newOfferId,
                    'percentage' => $oldOfferDiscount->percentage == 1,
                    'updated_at' => $oldOfferDiscount->updated_at,
                    'value' => $oldOfferDiscount->value
                ]);
            }
        }
    }
}
