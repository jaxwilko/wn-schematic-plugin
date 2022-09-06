<?php

namespace JaxWilko\Schematic\Classes;

use Cms\Classes\Theme;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Yaml;

class Scanner
{
    private static string $dirName = 'schematics';

    private static array $cache = [];

    public static function getRoot(): string
    {
        return Theme::getActiveTheme()->getPath() . DIRECTORY_SEPARATOR . static::$dirName;
    }

    public static function dirExists(): bool
    {
        return is_dir(static::getRoot());
    }

    public static function getFiles(): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(static::getRoot())) as $file) {
            if ($file->isDir() || $file->getExtension() !== 'yaml') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        asort($files);

        return $files;
    }

    public static function load(): array
    {
        if (static::$cache) {
            return static::$cache;
        }

        $data = [];

        foreach (static::getFiles() as $file) {
            $schematic = Yaml::parseFile($file);

            if (!$schematic || !isset($schematic['schematic'])) {
                continue;
            }

            $obj = new Schematic($schematic);
            $data[$obj->lname] = $obj;
        }

        return static::$cache = $data;
    }

    public static function getSorted(): array
    {
        $categories = [];
        $schematics = static::load();

        foreach ($schematics as $name => $schematic) {
            $categories[$schematic->category][$name] = $schematic;
        }

        foreach ($categories as &$category) {
            uasort($category, function ($a, $b) {
                return $a->order <=> $b->order;
            });
        }

        return $categories;
    }

    public static function getSchematic(string $key): ?Schematic
    {
        if (!static::$cache) {
            static::load();
        }

        return static::$cache[$key] ?? null;
    }
}
