# Drupal io_utils module

This module provides several utilities exposed via the 
drush command line to import, export, and manipulate
Drupal entities.

## Installation
This module requires some composer based dependencies.
From your drupal root run the following commands:

    composer require cyberitas/io_utils
    drush pm:enable -y io_utils


## Search and Replace Usage
Search and replace uses regular expressions, allowing back
references, to replace values in all published entities.
If the field-names option is omitted, it will search in
all fields and replace in all supported field types.

`
drush io-utils:replace "/^foo-(.*)-baz$/" "bar-$1-baz" --field-names body,field_example
`

## Export Usage
Creates a file called "drupal_post_17.json" or rewrites it, 
and puts in a json representation of a Drupal entity with 
ID  of 17, and the final 0 to allow exporting unpublished
entities.

`
io-utils:export-one 17 /example/entity_17.json 0
`

## Import Usage
Reads a file called "block-17.json", and puts saved information 
into a new Drupal block content

`
io-utils:import-one-block-content /example/entity_17.json
`

## Other
There are additional bulk operations, search operations,
and services available for use. Refer to the drush command
help for more information.
