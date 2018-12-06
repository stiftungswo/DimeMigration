<?php

require __DIR__ . '/vendor/autoload.php';

// Initialize all those different migrators
$customerMigrator = new \EntityMigrator\CustomerMigrator();
$employeeMigrator = new \EntityMigrator\EmployeeWithWorkPeriodMigrator();
$holidayMigrator = new \EntityMigrator\HolidayMigrator();
$offerMigrator = new \EntityMigrator\OfferMigrator();
$projectCategoryMigrator = new \EntityMigrator\ProjectCategoryMigrator();
$rateGroupMigrator = new \EntityMigrator\RateGroupMigrator();
$serviceMigrator = new \EntityMigrator\ServiceWithRatesMigrator();

// Save information about old and new entites id
$reverseCustomers = [];
$reverseServices = [];

// migrate employees and workperiods
$reverseEmployees = $employeeMigrator->doMigration();

// migrate holidays
$holidayMigrator->doMigration();

// migrate rate groups
$reverseRateGroups = $rateGroupMigrator->doMigration();

// migrate services and service rates
$reverseServices = $serviceMigrator->doMigration($reverseEmployees, $reverseRateGroups);

// migrate customers including their phones and addresses
$reverseCustomers = $customerMigrator->doMigration($reverseEmployees, $reverseRateGroups);

// migrate offers including their positions and their discounts
$offerMigrator->doMigration($reverseCustomers, $reverseEmployees, $reverseRateGroups, $reverseServices);

// migrate project categories
$reverseProjectCategories = $projectCategoryMigrator->doMigration();
