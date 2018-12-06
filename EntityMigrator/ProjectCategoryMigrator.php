<?php

namespace EntityMigrator;

use Main\HelperMethods;

class ProjectCategoryMigrator extends BaseMigrator
{
    /**
     * Copies the project categories from the old to the new dime
     * returns an array with the old and the new project category id
     * @return array
     */
    public function doMigration()
    {
        HelperMethods::printWithNewLine("\nMigrating project categories ...");
        $reverseProjectCategories = [];
        $oldProjectCategories = $this->capsule->connection('oldDime')->table('project_categories')->get();

        foreach ($oldProjectCategories as $oldProjectCategory) {
            $newProjectCategoryId = $this->capsule->connection('newDime')->table('project_categories')->insertGetId([
                'created_at' => $oldProjectCategory->created_at,
                'name' => $oldProjectCategory->name,
                'updated_at' => $oldProjectCategory->updated_at,
            ]);

            $reverseProjectCategories[$oldProjectCategory->id] = $newProjectCategoryId;
        }

        return $reverseProjectCategories;
    }
}
