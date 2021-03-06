<?php

namespace EntityMigrator;

use Main\HelperMethods;
use Illuminate\Hashing\BcryptHasher as Hasher;

class EmployeeWithWorkPeriodMigrator extends BaseMigrator
{
    /**
     * Migrates the employees and work periods from the old two the new dime
     * Returns an array where the key is the id from the old dime, and the value is the id in the new dime
     * @return array
     */
    public function doMigration()
    {
        HelperMethods::printWithNewLine("Migrating employees ...");
        $reverseEmployees = [];
        $oldEmployees = $this->capsule->connection('oldDime')->table('users')->get();

        foreach ($oldEmployees as $oldEmployee) {
            $hasher = new Hasher;

            HelperMethods::printWithNewLine("Migrating employee with mail " . $oldEmployee->email ?: '');

            $password = $this->randomPassword();
            file_put_contents('logins.txt', $oldEmployee->email . ' ' . $password . "\n", FILE_APPEND | LOCK_EX);

            $newEmployeeId = $this->capsule->connection('newDime')->table('employees')->insertGetId([
                'archived' => $oldEmployee->enabled != 1,
                'can_login' => $oldEmployee->enabled == 1,
                'created_at' => $oldEmployee->created_at,
                'email' => $oldEmployee->email ?: '',
                'first_name' => $oldEmployee->firstname ?: '',
                'holidays_per_year' => $oldEmployee->employeeholiday,
                'is_admin' => strpos($oldEmployee->roles, 'ROLE_SUPER_ADMIN') !== false,
                'last_name' => $oldEmployee->lastname ?: '',
                'password' => $hasher->make($password),
                'updated_at' => $oldEmployee->updated_at,
            ]);

            $reverseEmployees[$oldEmployee->id] = $newEmployeeId;

            $workPeriodsOfEmployee = $this->capsule->connection('oldDime')
                ->table('WorkingPeriods')
                ->where('employee_id', '=', $oldEmployee->id)->get();

            foreach ($workPeriodsOfEmployee as $workPeriodOfEmployee) {
                HelperMethods::printWithNewLine("Migrating Work Period with old ID " . $workPeriodOfEmployee->id);

                $this->capsule->connection('newDime')->table('work_periods')->insert([
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

        return $reverseEmployees;
    }

    // SOURCE: https://stackoverflow.com/a/6101969
    private function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
}
