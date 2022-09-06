<?php namespace JaxWilko\Schematic\Controllers;

use Backend\Classes\BackendController;
use Backend\Classes\MainMenuItem;
use BackendMenu;
use Backend\Classes\Controller;
use JaxWilko\Schematic\Classes\Scanner;
use Winter\Storm\Extension\ExtendableTrait;

/**
 * Schematic Backend Controller
 */
class Schematic extends Controller
{
    public array $listConfig = [];
    public array $formConfig = [];
    public array $relationConfig = [];

    protected string $schematicAction;
    protected string $schematicCategory;

    protected ?string $context;

    protected ?\JaxWilko\Schematic\Classes\Schematic $activeSchematic;

    protected MainMenuItem $activeMainMenuItem;

    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    public function __construct(string $schematic)
    {
        // dynamically generate form
        $this->activeSchematic = Scanner::getSchematic($schematic);

        $this->schematicAction = $this->activeSchematic->lname;
        $this->schematicCategory = $this->activeSchematic->lcategory;

        $this->listConfig = $this->activeSchematic->getListDefinition();
        $this->formConfig = $this->activeSchematic->getFormDefinition();
        $this->relationConfig = $this->activeSchematic->getRelationDefinition();

        // if we need relation dynamically add
        if ($this->activeSchematic->implementsRelation()) {
            $this->extendClassWith(\Backend\Behaviors\RelationController::class);
        }

        parent::__construct();

        BackendMenu::setContext('JaxWilko.Schematic', $this->schematicCategory, $this->schematicAction);
    }

    public function getSchematic(): \JaxWilko\Schematic\Classes\Schematic
    {
        return $this->activeSchematic;
    }

    public function getActiveMainMenuItem(): MainMenuItem
    {
        return $this->activeMainMenuItem ?? $this->activeMainMenuItem = \BackendMenu::getActiveMainMenuItem();
    }

    public function listExtendQuery($query, $scope)
    {
        return $query->where('schematic_type', $this->activeSchematic->name);
    }

    public function formBeforeSave(\JaxWilko\Schematic\Models\Schematic $model)
    {
        $model->schematic_type = $this->activeSchematic->name;
    }

    public function formExtendModel($model)
    {
        if (in_array($this->formGetContext(), ['create', 'update'])) {
            $this->activeSchematic->bindRelations($model);
        }

        return $model;
    }
}
