<?php

require __DIR__ . '/vendor/autoload.php';

// Initialize all those different migrators
$customerMigrator = new \EntityMigrator\CustomerMigrator();
$employeeMigrator = new \EntityMigrator\EmployeeWithWorkPeriodMigrator();
$holidayMigrator = new \EntityMigrator\HolidayMigrator();
$offerMigrator = new \EntityMigrator\OfferMigrator();
$projectMigrator = new \EntityMigrator\ProjectMigrator();
$projectCategoryMigrator = new \EntityMigrator\ProjectCategoryMigrator();
$projectCommentMigrator = new \EntityMigrator\ProjectCommentMigrator();
$projectPositionWithEffortMigrate = new \EntityMigrator\ProjectPositionWithEffortsMigrator();
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

// migrate projects
$projectMigrator->doMigration($reverseCustomers, $reverseEmployees, $reverseProjectCategories, $reverseRateGroups, $reverseServices);

// migrate project comments
$projectCommentMigrator->doMigration($reverseEmployees);

// migrate project positions with efforts
$projectPositionWithEffortMigrate->doMigrate($reverseEmployees, $reverseServices);
