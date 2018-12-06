<?php

namespace EntityMigrator;

use Main\HelperMethods;

class HolidayMigrator extends BaseMigrator
{
    public function doMigration()
    {
        HelperMethods::printWithNewLine("\nMigrating holidays ...");
        $oldHolidays = $this->capsule->connection('oldDime')->table('holidays')->get();
        foreach ($oldHolidays as $oldHoliday) {
            $this->capsule->connection('newDime')->table('holidays')->insert([
                'created_at' => $oldHoliday->created_at,
                'date' => $oldHoliday->date,
                'duration' => intval($oldHoliday->duration / 60),
                'name' => 'Unbekannt',
                'updated_at' => $oldHoliday->updated_at,
            ]);
        }
    }
}
