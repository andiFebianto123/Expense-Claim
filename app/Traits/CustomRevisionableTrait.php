<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Venturecraft\Revisionable\Revisionable;
use Venturecraft\Revisionable\RevisionableTrait;

trait CustomRevisionableTrait
{
    use RevisionableTrait;

    public function postCreate()
    {
        // Check if we should store creations in our revision history
        // Set this value to true in your model if you want to
        if(empty($this->revisionCreationsEnabled))
        {
            // We should not store creations.
            return false;
        }

        if ((!isset($this->revisionEnabled) || $this->revisionEnabled))
        {
            $revisions[] = array(
                'revisionable_type' => $this->getMorphClass(),
                'revisionable_id' => $this->getKey(),
                'key' => self::CREATED_AT,
                'old_value' => null,
                'new_value' => $this->{self::CREATED_AT},
                'data' => json_encode($this->toArray()),
                'user_id' => $this->getSystemUserId(),
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            );

            //Determine if there are any additional fields we'd like to add to our model contained in the config file, and
            //get them into an array.
            $revisions = array_merge($revisions[0], $this->getAdditionalFields());

            $revision = Revisionable::newModel();
            \DB::table($revision->getTable())->insert($revisions);
            \Event::dispatch('revisionable.created', array('model' => $this, 'revisions' => $revisions));
        }

    }

    public function postForceDelete()
    {
        if (empty($this->revisionForceDeleteEnabled)) {
            return false;
        }

        if ((!isset($this->revisionEnabled) || $this->revisionEnabled)
            && (($this->isSoftDelete() && $this->isForceDeleting()) || !$this->isSoftDelete())) {

            $revisions[] = array(
                'revisionable_type' => $this->getMorphClass(),
                'revisionable_id' => $this->getKey(),
                'key' => self::CREATED_AT,
                'old_value' => $this->{self::CREATED_AT},
                'new_value' => null,
                'data' => json_encode($this->toArray()),
                'user_id' => $this->getSystemUserId(),
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
                'ip_address' => request()->ip()
            );

            $revision = Revisionable::newModel();
            \DB::table($revision->getTable())->insert($revisions);
            \Event::dispatch('revisionable.deleted', array('model' => $this, 'revisions' => $revisions));
        }
    }

    public function getAdditionalFields()
    {
        $additional = [];
        //Determine if there are any additional fields we'd like to add to our model contained in the config file, and
        //get them into an array.
        $fields = config('revisionable.additional_fields', []);
        foreach($fields as $field) {
            if(Arr::has($this->originalData, $field)) {
                $additional[$field]  =  Arr::get($this->originalData, $field);
            }
        }

        $additional['ip_address'] = request()->ip();

        return $additional;
    }
}