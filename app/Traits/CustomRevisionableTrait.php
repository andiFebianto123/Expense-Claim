<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Venturecraft\Revisionable\Revisionable;
use Venturecraft\Revisionable\RevisionableTrait;

trait CustomRevisionableTrait
{
    use RevisionableTrait;

    public function postSave()
    {
        if (isset($this->historyLimit) && $this->revisionHistory()->count() >= $this->historyLimit) {
            $LimitReached = true;
        } else {
            $LimitReached = false;
        }
        if (isset($this->revisionCleanup)) {
            $RevisionCleanup = $this->revisionCleanup;
        } else {
            $RevisionCleanup = false;
        }

        // check if the model already exists
        if (((!isset($this->revisionEnabled) || $this->revisionEnabled) && $this->updating) && (!$LimitReached || $RevisionCleanup)) {
            // if it does, it means we're updating

            $changes_to_record = $this->changedRevisionableFields();

            $revisions = array();

            foreach ($changes_to_record as $key => $change) {
                $original = array(
                    'revisionable_type' => $this->getMorphClass(),
                    'revisionable_id' => $this->getKey(),
                    'key' => $key,
                    'old_value' => Arr::get($this->originalData, $key),
                    'new_value' => $this->updatedData[$key],
                    'user_id' => $this->getSystemUserId(),
                    'created_at' => new \DateTime(),
                    'updated_at' => new \DateTime(),
                );

                $revisions[] = array_merge($original, $this->getAdditionalFields());
            }

            if (count($revisions) > 0) {
                if ($LimitReached && $RevisionCleanup) {
                    $toDelete = $this->revisionHistory()->orderBy('id', 'asc')->limit(count($revisions))->get();
                    foreach ($toDelete as $delete) {
                        $delete->delete();
                    }
                }

                if(!isset($GLOBALS['currentRevisionable'])){
                    $GLOBALS['currentRevisionable'] = [];
                }
                $GLOBALS['currentRevisionable'] = array_merge($GLOBALS['currentRevisionable'], $revisions);
                // $revision = Revisionable::newModel();
                // \DB::table($revision->getTable())->insert($revisions);
                // \Event::dispatch('revisionable.saved', array('model' => $this, 'revisions' => $revisions));
            }
        }
    }

    /**
     * Called after record successfully created
     */
    public function postCreate()
    {

        // Check if we should store creations in our revision history
        // Set this value to true in your model if you want to
        if (empty($this->revisionCreationsEnabled)) {
            // We should not store creations.
            return false;
        }

        if ((!isset($this->revisionEnabled) || $this->revisionEnabled)) {
            $revisions[] = array(
                'revisionable_type' => $this->getMorphClass(),
                'revisionable_id' => $this->getKey(),
                'key' => self::CREATED_AT,
                'old_value' => null,
                'new_value' => $this->{self::CREATED_AT},
                'user_id' => $this->getSystemUserId(),
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            );

            //Determine if there are any additional fields we'd like to add to our model contained in the config file, and
            //get them into an array.
            $revisions = array_merge($revisions[0], $this->getAdditionalFields());
            if(!isset($GLOBALS['currentRevisionable'])){
                $GLOBALS['currentRevisionable'] = [];
            }
            $GLOBALS['currentRevisionable'] = array_merge($GLOBALS['currentRevisionable'], $revisions);
            
            // $revision = Revisionable::newModel();
            // \DB::table($revision->getTable())->insert($revisions);
            // \Event::dispatch('revisionable.created', array('model' => $this, 'revisions' => $revisions));
        }
    }

    public function postDelete(){
        //noop
    }


    public function postForceDelete(){
        //noop
    }
}
