<?php

require __DIR__ . '/vendor/autoload.php';
require 'HelperMethods.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Hashing\BcryptHasher as Hasher;

$capsule = new Capsule;

// TODO Add .env package and configure this with .env files
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3010',
    'database' => 'dime',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
], 'oldDime');

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '33306',
    'database' => 'dime',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
], 'newDime');

$capsule->setAsGlobal();

// Save information about old and new entites id
$reverseEmployees = [];
$reverseRateGroups = [];
$reverseServices = [];

HelperMethods::printWithNewLine("Migrating employees ...");
$oldEmployees = Capsule::connection('oldDime')->table('users')->get();
foreach ($oldEmployees as $oldEmployee) {
    $hasher = new Hasher;

    if ($oldEmployee->firstname && $oldEmployee->lastname && $oldEmployee->email != "") {
        HelperMethods::printWithNewLine("Migrating employee with mail " . $oldEmployee->email);

        // TODO Dynamically generate a fine password and save it to a file on the disk
        $newEmployeeId = Capsule::connection('newDime')->table('employees')->insertGetId([
            'archived' => $oldEmployee->enabled != 1,
            'can_login' => $oldEmployee->enabled == 1,
            'created_at' => $oldEmployee->created_at,
            'email' => $oldEmployee->email,
            'first_name' => $oldEmployee->firstname,
            'holidays_per_year' => $oldEmployee->employeeholiday,
            'is_admin' => strpos($oldEmployee->roles, 'ROLE_SUPER_ADMIN') !== false,
            'last_name' => $oldEmployee->lastname,
            'password' => $hasher->make('Welcome01'),
            'updated_at' => $oldEmployee->updated_at,
        ]);

        $reverseEmployees[$oldEmployee->id] = $newEmployeeId;

        $workPeriodsOfEmployee = Capsule::connection('oldDime')
            ->table('WorkingPeriods')
            ->where('employee_id', '=', $oldEmployee->id)->get();

        foreach ($workPeriodsOfEmployee as $workPeriodOfEmployee) {
            HelperMethods::printWithNewLine("Migrating Work Period with old ID " . $workPeriodOfEmployee->id);

            Capsule::connection('newDime')->table('work_periods')->insert([
                'created_at' => $workPeriodOfEmployee->created_at,
                'employee_id' => $newEmployeeId,
                'end' => $workPeriodOfEmployee->end,
                'pensum' => intval($workPeriodOfEmployee->pensum * 100),
                'start' => $workPeriodOfEmployee->start,
                'updated_at' => $workPeriodOfEmployee->updated_at,
                'vacation_takeover' => $workPeriodOfEmployee->last_year_holiday_balance ? $workPeriodOfEmployee->last_year_holiday_balance * 60 : 0,
                'yearly_vacation_budget' => $workPeriodOfEmployee->yearly_employee_vacation_budget * 60 * 8.4
            ]);
        }
    }
}

HelperMethods::printWithNewLine("\nMigrating holidays ...");
$oldHolidays = Capsule::connection('oldDime')->table('holidays')->get();
foreach ($oldHolidays as $oldHoliday) {
    Capsule::connection('newDime')->table('holidays')->insert([
        'created_at' => $oldHoliday->created_at,
        'date' => $oldHoliday->date,
        'duration' => intval($oldHoliday->duration / 60),
        'name' => 'Unbekannt',
        'updated_at' => $oldHoliday->updated_at,
    ]);
}

HelperMethods::printWithNewLine("\nMigrating rate groups ...");
$oldRateGroups = Capsule::connection('oldDime')->table('rate_groups')->get();
foreach ($oldRateGroups as $oldRateGroup) {
    $rateGroupNewId = Capsule::connection('newDime')->table('rate_groups')->insertGetId([
        'description' => $oldRateGroup->description,
        'name' => $oldRateGroup->name,
    ]);

    $reverseRateGroups[$oldRateGroup->id] = $rateGroupNewId;
}

HelperMethods::printWithNewLine("\nMigrating services ...");
$oldServices = Capsule::connection('oldDime')->table('services')->get();
foreach ($oldServices as $oldService) {
    HelperMethods::printWithNewLine("Migrating Service " . $oldService->name);

    $newServiceId = Capsule::connection('newDime')->table('services')->insertGetId([
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

    $ratesOfService = Capsule::connection('oldDime')->table('rates')
        ->where('service_id', '=', $oldService->id)->get();

    foreach ($ratesOfService as $rateOfService) {
        // Check if we already have the combination of rateunit and rateunittypeid
        $matchingNewRateUnits = Capsule::connection('newDime')->table('rate_units')->where([
            ['billing_unit', "=", $rateOfService->rate_unit],
            ['effort_unit', '=', $rateOfService->rateUnitType_id]
        ])->get();

        if ($matchingNewRateUnits->isEmpty()) {
            // find old rate unit
            $oldRateUnit = Capsule::connection('oldDime')->table('rateunittypes')
                ->where('id', '=', $rateOfService->rateUnitType_id)->get()->first();

            // create new rate unit
            // TODO change name after rate units got cleaned up on production
            $newRateUnitId = Capsule::connection('newDime')->table('rate_units')->insertGetId([
                'archived' => false,
                'billing_unit' => $rateOfService->rate_unit,
                'created_at' => new DateTime(),
                'effort_unit' => $rateOfService->rateUnitType_id,
                'factor' => $oldRateUnit->factor == 1 ? 1 : $oldRateUnit->factor / 60,
                'name' => $rateOfService->rate_unit,
                'updated_at' => new DateTime(),
            ]);
        } else {
            $newRateUnitId = $matchingNewRateUnits->first()->id;
        }

        Capsule::connection('newDime')->table('service_rates')->insert([
            'created_at' => $rateOfService->created_at,
            'rate_group_id' => $reverseRateGroups[$rateOfService->rate_group_id],
            'rate_unit_id' => $newRateUnitId,
            'service_id' => $newServiceId,
            'updated_at' => $rateOfService->updated_at,
            'value' => HelperMethods::examineMoneyValue($rateOfService->rate_value)
        ]);
    }
}

HelperMethods::printWithNewLine("\nMigrating customers ...");
$oldCustomers = Capsule::connection('oldDime')->table('customers')->get();
foreach ($oldCustomers as $oldCustomer) {
    // if the existing customer has the company field out, check if we have this company already
    // if not, create it, otherweise use the existing one
    if (!empty($oldCustomer->company)) {
        $potentialAlreadyMigratedCompany = Capsule::connection('newDime')->table('customers')->where([
            ['name', '=', $oldCustomer->company]
        ])->first();

        if (is_null($potentialAlreadyMigratedCompany)) {
            HelperMethods::printWithNewLine("Creating a new company " . $oldCustomer->company);
            $newCompanyId = Capsule::connection('newDime')->table('customers')->insertGetId([
                'name' => $oldCustomer->company,
                'hidden' => $oldCustomer->system_customer != 1,
                'type' => 'company',
                'rate_group_id' => $reverseRateGroups[$oldCustomer->rate_group_id]
                ]);
        } else {
            $newCompanyId = $potentialAlreadyMigratedCompany->id;
        }
    }

    // create new person
    $partForName = explode(' ', !empty($oldCustomer->full_name) ?: $oldCustomer->name);
    $lastName = array_pop($partForName);
    $firstName = array(implode(' ', $partForName), $lastName)[0];

    HelperMethods::printWithNewLine("Creating a new person " . $firstName . ' ' . $lastName);
    $newPersonId = Capsule::connection('newDime')->table('customers')->insertGetId([
        'company_id' => $oldCustomer->company ? $newCompanyId : null,
        'comment' => $oldCustomer->comment,
        'created_at' => $oldCustomer->created_at,
        'created_by' => $reverseEmployees[$oldCustomer->user_id],
        'department' => $oldCustomer->department,
        'email' => $oldCustomer->email,
        'hidden' => $oldCustomer->system_customer != 1,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'rate_group_id' => $oldCustomer->rate_group_id,
        'type' => 'person',
        'salutation' => $oldCustomer->salutation,
        'updated_at' => $oldCustomer->updated_at
    ]);

    // create new phone if set on old customer
    if (!empty($oldCustomer->phone)) {
        HelperMethods::printWithNewLine("Creating new phone for " . $firstName . ' ' . $lastName);
        Capsule::connection('newDime')->table('phones')->insert([
            'category' => $oldCustomer->company ? 2 : 3,
            'customer_id' => $newPersonId,
            'number' => $oldCustomer->phone,
        ]);
    }

    // create new mobile number if set on old customer
    if (!empty($oldCustomer->mobilephone)) {
        HelperMethods::printWithNewLine("Creating new mobile phone for " . $firstName . ' ' . $lastName);
        Capsule::connection('newDime')->table('phones')->insert([
            'category' => 4,
            'customer_id' => $newPersonId,
            'number' => $oldCustomer->mobilephone,
        ]);
    }

    $addressOfOldCustomer = $ratesOfService = Capsule::connection('oldDime')->table('address')
        ->where('id', '=', $oldCustomer->address_id)->first();

    if (!is_null($addressOfOldCustomer)) {
        if (!empty($addressOfOldCustomer->street) && !empty($addressOfOldCustomer->plz) && !empty($addressOfOldCustomer->city)) {
            HelperMethods::printWithNewLine("Creating new address for " . $firstName . ' ' . $lastName);
            Capsule::connection('newDime')->table('addresses')->updateOrInsert([
                'city' => $addressOfOldCustomer->city,
                'country' => $addressOfOldCustomer->country,
                'customer_id' => $oldCustomer->company ? $newCompanyId : $newPersonId,
                'postcode' => $addressOfOldCustomer->plz,
                'street' => $addressOfOldCustomer->street,
                'supplement' => $addressOfOldCustomer->supplement,
            ], [
                'created_at' => $oldCustomer->created_at,
                'created_by' => $reverseEmployees[$oldCustomer->user_id],
                'city' => $addressOfOldCustomer->city,
                'country' => $addressOfOldCustomer->country,
                'customer_id' => $oldCustomer->company ? $newCompanyId : $newPersonId,
                'postcode' => $addressOfOldCustomer->plz,
                'street' => $addressOfOldCustomer->street,
                'supplement' => $addressOfOldCustomer->supplement,
                'updated_at' => $oldCustomer->updated_at
            ]);
        } else {
            HelperMethods::printWithNewLine("Missing Street, PLZ or city for new address for " . $firstName . ' ' . $lastName);
        }
    }
}
