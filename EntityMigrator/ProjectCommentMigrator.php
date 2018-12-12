<?php

namespace EntityMigrator;

use Main\HelperMethods;

class ProjectCommentMigrator extends BaseMigrator
{
    /**
     * Migrates the project comments
     * @param array $reverseEmployees
     */
    public function doMigration(array $reverseEmployees)
    {
        HelperMethods::printWithNewLine("\nMigrating project comments");

        $oldProjectComments = $this->capsule->connection('oldDime')->table('project_comments')->get();
        foreach ($oldProjectComments as $oldProjectComment) {
            $this->capsule->connection('newDime')->table('project_comments')->insert([
                'comment' => $oldProjectComment->comment,
                'created_at' => $oldProjectComment->created_at,
                'date' => $oldProjectComment->date,
                'project_id' => $oldProjectComment->project_id,
                'updated_at' => $oldProjectComment->updated_at,
                'updated_by' => $oldProjectComment->user_id ? $reverseEmployees[$oldProjectComment->user_id] : null,
            ]);
        }
    }
}
