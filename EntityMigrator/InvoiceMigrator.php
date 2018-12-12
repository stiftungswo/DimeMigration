<?php

namespace EntityMigrator;

use Main\HelperMethods;

class InvoiceMigrator extends BaseMigrator
{
    public function doMigration(array $reverseCostgroups, array $reverseCustomers, array $reverseEmployees, array $reverseProjectPositions)
    {
        $addressMigrator = new AddressMigrator();
        $rateUnitMigrator = new RateUnitMigrator();
        $oldInvoices = $this->capsule->connection('oldDime')->table('invoices')->get();

        foreach ($oldInvoices as $oldInvoice) {
            HelperMethods::printWithNewLine("\nMigrating invoice " . $oldInvoice->id);
            $oldAddressId = null;

            if ($oldInvoice->project_id) {
                $project = $this->capsule->connection('newDime')->table('projects')->find($oldInvoice->project_id);

                if ($project) {
                    $newAddressId = $project->address_id;
                } else {
                    $newAddressId = null;
                }
            } else {
                $oldCustomer = $this->capsule->connection('oldDime')->table('customers')->where([
                    'id' => $oldInvoice->customer_id
                ])->first();
                $oldAddressId = $oldCustomer ? $oldCustomer->address_id : null;

                if (is_null($oldAddressId)) {
                    $newAddressId = null;
                } else {
                    $oldAddress = $this->capsule->connection('oldDime')->table('address')->where([
                        'id' => $oldAddressId
                    ])->first();
                    $newAddressId = $addressMigrator->doMigration($oldAddress, empty($reverseCustomers[$oldInvoice->customer_id]['company']) ? $reverseCustomers[$oldInvoice->customer_id]['person'] : $reverseCustomers[$oldInvoice->customer_id]['company'])->id;
                }
            }

            $this->capsule->connection('newDime')->table('invoices')->insert([
                'accountant_id' => $oldInvoice->accountant_id ? $reverseEmployees[$oldInvoice->accountant_id] : null,
                'address_id' => $newAddressId,
                'created_at' => $oldInvoice->created_at,
                'customer_id' => $oldInvoice->customer_id ? $reverseCustomers[$oldInvoice->customer_id]['person'] ?: $reverseCustomers[$oldInvoice->customer_id]['company'] : null,
                'description' => $oldInvoice->description,
                'end' => $oldInvoice->end,
                'fixed_price' => HelperMethods::examineMoneyValue($oldInvoice->fixed_price, true),
                'id' => $oldInvoice->id,
                'project_id' => $oldInvoice->project_id,
                'name' => $oldInvoice->name,
                'start' => $oldInvoice->start,
                'updated_at' => $oldInvoice->updated_at,
                'updated_by' => $oldInvoice->user_id ? $reverseEmployees[$oldInvoice->user_id] : null,
            ]);

            // migrate the positions / items
            $oldPositionsOfInvoice = $this->capsule->connection('oldDime')->table('invoice_items')->where([
                'invoice_id' => $oldInvoice->id
            ])->get();

            HelperMethods::printWithNewLine("Migrating positions for " . $oldInvoice->id);
            foreach ($oldPositionsOfInvoice as $oldInvoicePosition) {
                $this->capsule->connection('newDime')->table('invoice_positions')->insert([
                    'amount' => $oldInvoicePosition->amount,
                    'created_at' => $oldInvoicePosition->created_at,
                    'description' => $oldInvoicePosition->name,
                    'invoice_id' => $oldInvoice->id,
                    'order' => $oldInvoicePosition->order_no,
                    'price_per_rate' => HelperMethods::examineMoneyValue($oldInvoicePosition->rate_value),
                    'project_position_id' => $oldInvoicePosition->activity_id ? $reverseProjectPositions[$oldInvoicePosition->activity_id] : null,
                    'rate_unit_id' => $rateUnitMigrator->doMigration($oldInvoicePosition->rateUnit, $oldInvoicePosition->rateUnit, true),
                    'updated_at' => $oldInvoicePosition->updated_at,
                    'vat' => $oldInvoicePosition->vat
                ]);
            }

            // migrate discounts
            $oldDiscountsOfInvoice = $this->capsule->connection('oldDime')->table('invoiceDiscounts')->where([
                'invoice_id' => $oldInvoice->id,
            ])->get();

            HelperMethods::printWithNewLine("Migrating discounts for " . $oldInvoice->id);
            foreach ($oldDiscountsOfInvoice as $oldInvoiceDiscount) {
                $this->capsule->connection('newDime')->table('invoice_discounts')->insert([
                    'created_at' => $oldInvoiceDiscount->created_at,
                    'invoice_id' => $oldInvoice->id,
                    'name' => $oldInvoiceDiscount->name,
                    'percentage' => $oldInvoiceDiscount->percentage == 1,
                    'updated_at' => $oldInvoiceDiscount->updated_at,
                    'updated_by' => $oldInvoiceDiscount->user_id ? $reverseEmployees[$oldInvoiceDiscount->user_id] : null,
                    'value' => $oldInvoiceDiscount->percentage == 1 ? $oldInvoiceDiscount->value : HelperMethods::examineMoneyValue($oldInvoiceDiscount->value)
                ]);
            }

            // migrate costgroup distributions
            $oldCostgroupDistributionsOfInvoice = $this->capsule->connection('oldDime')->table('invoice_costgroups')->where([
                'invoice_id' => $oldInvoice->id
            ])->get();

            HelperMethods::printWithNewLine("Migrating costgroup distributions for " . $oldInvoice->id);
            foreach ($oldCostgroupDistributionsOfInvoice as $oldCostgroupDistribution) {
                $this->capsule->connection('newDime')->table('costgroup_distributions')->insert([
                    'created_at' => $oldCostgroupDistribution->created_at,
                    'costgroup_number' => $reverseCostgroups[$oldCostgroupDistribution->costgroup_id],
                    'invoice_id' => $oldInvoice->id,
                    'updated_at' => $oldCostgroupDistribution->updated_at,
                    'weight' => $oldCostgroupDistribution->weight,
                ]);
            }
        }
    }
}
