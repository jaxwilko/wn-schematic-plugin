# Schematic

This plugin allows for dynamically created forms, lists and menu items from simple yaml files.

### Usage

Schematics are loaded from `themes/{activeTheme}/schematics` and look like:

```yaml
schematic:
    name: Blog.Post
    icon: icon-files-o
    order: 100
    hasMany:
        - Category

title:
    label: "Title"
    span: full

content:
    label: "Content"
    type: richeditor

image:
    label: "Image"
    type: mediafinder
```

### Known issues

This is a WIP and there are currently lots of bugs including relationships between schematic models

