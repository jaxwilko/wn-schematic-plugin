<?php

namespace JaxWilko\Schematic;

use Cms\Classes\Theme;
use JaxWilko\Schematic\Classes\Scanner;
use JaxWilko\Schematic\Controllers\Schematic;
use System\Classes\PluginBase;
use Backend\Classes\NavigationManager;
use Event;
use Route;
use Config;
use Backend;
use BackendMenu;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Winter Schematic',
            'description' => 'Being dynamic dynamically',
            'author'      => 'Jack Wilkinson',
            'icon'        => 'icon-leaf'
        ];
    }

    public function registerComponents()
    {
        return [];
    }

    public function boot()
    {
        if (!Scanner::dirExists() || !Scanner::load()) {
            return;
        }

        Event::listen('backend.beforeRoute', function () {
            Route::group([
                'middleware' => ['web'],
                'prefix' => Config::get('cms.backendUri', 'backend')
            ], function () {
                Route::any('jaxwilko/schematic/{slug?}', function (string $slug) {
                    list($schematic, $action, $id) = array_pad(explode('/', $slug), 3, null);

                    $controller = new Schematic($schematic);

                    return match ($action) {
                        'create' => $controller->run($action),
                        'update' => $controller->run($action, [
                            'id' => $id
                        ]),
                        default => $controller->run('index'),
                    };
                })->where('slug', '(.*)?');
            });
        });

        Event::listen('backend.menu.extendItems', function (NavigationManager $navigationManager) {
            $schematics = Scanner::getSorted();
            foreach ($schematics as $root => $list) {
                $rootKey = strtolower($root);
                $config = [
                    $rootKey => [
                        'label'       => $root,
                        'url'         => null,
                        'icon'        => null,
                        'permissions' => [/* todo */],
                        'order'       => 200,
                        'sideMenu' => []
                    ]
                ];

                foreach ($list as $key => $schematic) {
                    if (!$config[$rootKey]['url']) {
                        $config[$rootKey]['url'] = Backend::url('jaxwilko/schematic/' . $schematic->lname);
                        $config[$rootKey]['icon'] = $schematic->icon;
                    }

                    $config[$rootKey]['sideMenu'][$schematic->lname] = [
                        'label'       => $schematic->name,
                        'icon'        => $schematic->icon,
                        'url'         => Backend::url('jaxwilko/schematic/' . $schematic->lname),
                        'permissions' => [/* todo */]
                    ];
                }

                $navigationManager->addMainMenuItems('JaxWilko.Schematic', $config);
            }
        });

        Models\Schematic::extend(function (Models\Schematic $model) {
//            $model->bindEvent('model.beforeSave', function () use ($model) {
//
//                $relationLower = 'category';
//                $data = $model->toArray();
//                $toSave = [];
//
//                if (!isset($data[$relationLower]) || !$data[$relationLower]) {
//                    return;
//                }
//
//                if (!isset($data[$relationLower][0])) {
//                    $toSave[$relationLower] = $data[$relationLower]['id'];
//                }
//
//                foreach ($data[$relationLower] as $record) {
//                    $toSave[$relationLower][] = $record['id'];
//                }
//
//                $model->schematic_data = array_merge($model->schematic_data, $toSave);
//
//                $model->unsetRelation('category');
//                $model->offsetUnset('category');
////                unset($model->hasMany['category']);
//                dd($model);
//            });
        });
    }
}
