<?php

namespace JaxWilko\Schematic\Models;

use JaxWilko\Schematic\Classes\Scanner;
use Model;

/**
 * Schematic Model
 */
class Schematic extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'jaxwilko_schematic_schematics';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'schematic_type',
        'schematic_data'
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [
        'schematic_data'
    ];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $hasOneThrough = [];
    public $hasManyThrough = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    protected static function booted()
    {
        static::retrieved(function ($model) {
            $schematic = Scanner::getSchematic(strtolower($model->schematic_type));
            if (!$schematic) {
                return;
            }

            $schematic->bindRelations($model);
        });
    }
}
