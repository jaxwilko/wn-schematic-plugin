<?php

namespace JaxWilko\Schematic\Classes;

use Winter\Storm\Exception\ApplicationException;

class Schematic
{
    protected object $settings;
    protected array $config;
    protected bool $hasRelation;

    protected array $relationTypes = [
        'hasMany',
        'hasOne',
    ];

    public function __construct(array $schematic)
    {
        $this->settings = (object) $schematic['schematic'];
        unset($schematic['schematic']);

        if (!isset($this->settings->name)) {
            throw new ApplicationException('Schematics must include a name');
        }

        if (str_contains($this->settings->name, '.')) {
            list($this->settings->category, $this->settings->name) = explode('.', $this->settings->name, 2);
        }

        $this->settings->lname = strtolower($this->settings->name);
        $this->settings->lcategory = strtolower($this->settings->category);

        $this->config = $schematic;
    }

    public function __get(string $name): mixed
    {
        return $this->settings->{$name} ?? null;
    }

    public function implementsRelation(): bool
    {
        if (isset($this->hasRelation)) {
            return $this->hasRelation;
        }

        foreach ($this->relationTypes as $relation) {
            if (isset($this->settings->{$relation})) {
                return $this->hasRelation = true;
            }
        }

        return $this->hasRelation = false;
    }

    public function getListDefinition(): array
    {
        $definition = [
            'list' => [
                'title' => str_plural($this->settings->name),
                'list' => [
                    'columns' => []
                ],
                'modelClass' => \JaxWilko\Schematic\Models\Schematic::class,
                'recordUrl' => 'jaxwilko/schematic/' . $this->settings->lname . '/update/:id',
                'showCheckboxes' => true,
                'recordsPerPage' => 20,
                'perPageOptions' => [20, 40, 80, 100, 120],
                'showPageNumbers' => true,
                'showSetup' => true,
                'toolbar' => [
                    'buttons' => 'list_toolbar',
                    'search' => [
                        'prompt' => 'backend::lang.list.search_prompt'
                    ]
                ]
            ]
        ];

        foreach ($this->config as $key => $settings) {
            if (isset($settings['type']) && in_array($settings['type'], ['mediafinder', 'richeditor'])) {
                continue;
            }
            $definition['list']['list']['columns'][sprintf('schematic_data[%s]', $key)] = $settings;
        }

        return $definition;
    }

    public function getFormDefinition(): array
    {
        $definition = [
            'name' => $this->settings->name,
            'form' => [
                'fields' => []
            ],
            'modelClass' => \JaxWilko\Schematic\Models\Schematic::class,
            'defaultRedirect' => 'jaxwilko/schematic/' . $this->settings->lname,
            'create' => [
                'title' => 'backend::lang.form.create_title',
                'redirect' => 'jaxwilko/schematic/' . $this->settings->lname . '/update/:id',
                'redirectClose' => 'jaxwilko/schematic/' . $this->settings->lname
            ],
            'update' => [
                'title' => 'backend::lang.form.update_title',
                'redirect' => 'jaxwilko/schematic/' . $this->settings->lname,
                'redirectClose' => 'jaxwilko/schematic/' . $this->settings->lname
            ],
            'preview' => [
                'title' => 'backend::lang.form.preview_title',
            ]
        ];

        foreach ($this->config as $key => $settings) {
            $definition['form']['fields'][sprintf('schematic_data[%s]', $key)] = $settings;
        }

        if ($this->implementsRelation()) {
            foreach ($this->relationTypes as $relationType) {
                if (!isset($this->settings->{$relationType})) {
                    continue;
                }

                $relations = $this->settings->{$relationType};

                if (!is_array($relations)) {
                    $relations = [$relations];
                }

                foreach ($relations as $relation) {
                    $definition['form']['fields'][sprintf('schematic_data[%s]', strtolower($relation))] = [
                        'label' => $relation,
                        'type' => 'relation',
                    ];
                }
            }
        }

        return $definition;
    }

    public function getRelationDefinition(): array
    {
        if (!$this->implementsRelation()) {
            return [];
        }

        $definition = [];

        foreach ($this->relationTypes as $relationType) {
            if (!isset($this->settings->{$relationType})) {
                continue;
            }

            $relations = $this->settings->{$relationType};

            if (!is_array($relations)) {
                $relations = [$relations];
            }

            foreach ($relations as $relation) {
                $relationLower = strtolower($relation);
                $relationSchematic = Scanner::getSchematic($relationLower);
                $definition[sprintf('schematic_data[%s]', $relationLower)] = [
                    'label' => $relation,
                    'manage' => [
                        'list' => $relationSchematic->getListDefinition(),
                        'form' => $relationSchematic->getFormDefinition()
                    ],
                    'view' => [
                        'list' => $relationSchematic->getListDefinition(),
                        'toolbarButtons' => 'add|remove'
                    ]
                ];
            }
        }

        return $definition;
    }

    public function bindRelations(\JaxWilko\Schematic\Models\Schematic $model): \JaxWilko\Schematic\Models\Schematic
    {
        foreach ($this->relationTypes as $relationType) {
            if (!isset($this->settings->{$relationType})) {
                continue;
            }

            $relations = $this->settings->{$relationType};

            if (!is_array($relations)) {
                $relations = [$relations];
            }

            foreach ($relations as $relation) {
                $relationLower = strtolower($relation);

                $model->{$relationType}['schematic_data[' . $relationLower . ']'] = [
                    \JaxWilko\Schematic\Models\Schematic::class,
                    'key' => 'schematic_data->' . $relationLower,
                    'foreign_id' => 'id',
                    'table' => 'jaxwilko_schematic_schematics',
                    'conditions' => sprintf('schematic_type = \'%s\'', $relation)
                ];

                $model->addFillable('schematic_data[' . $relationLower . ']');

                if (!isset($model->schematic_data[$relationLower]) || !$model->schematic_data[$relationLower]) {
                    $model->attributes[$relationLower] = new \JaxWilko\Schematic\Models\Schematic();
//                    $model->schematic_data[$relationLower] = new \JaxWilko\Schematic\Models\Schematic();
                }

                $model::saving(function ($model) use ($relation, $relationLower){
//                    $data = $model->toArray();
//                    $toSave = [];
//
//                    if (!isset($data[$relationLower]) || !$data[$relationLower]) {
//                        return;
//                    }
//
//                    if (!isset($data[$relationLower][0])) {
//                        $toSave[$relationLower] = $data[$relationLower]['id'];
//                    }
//
//                    foreach ($data[$relationLower] as $record) {
//                        $toSave[$relationLower][] = $record['id'];
//                    }
//
//                    $model->schematic_data = array_merge($model->schematic_data, $toSave);
//
                    unset($model->{$relationLower});
//
//                    return;
                });
            }
        }

//        dd($model);

        return $model;
    }
}
