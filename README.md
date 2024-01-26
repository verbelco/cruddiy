# CRUD forms generator for WaterWeb
This repository contains the CRUD generator that is used to generate pages in WaterWeb. It is a fork from [cruddiy](https://github.com/jan-vandenberg/cruddiy/) by jan-vandenberg.

Since only one person is working on this repository, two checklist are used to show what features / bugs are worked on and also what features are implemented. 
I have no intention of merging this back into the original repository, since the differences have become too big.

## New features compared to cruddiy
- [x] Show Column Comments from SQL as Tooltips for the column name.
- [x] Show SQL errors when updating, creating or deleting. 
- [x] Make foreign key references clickable.
- [x] Preview foreign key references (using a tooltip?), also make it selectable (on creation) which columns appear in this reference.
- [x] For a record that is referenced by other records, provide a link to these other table records.
- [x] Add an update, delete and read button to the update, delete and read pages.
- [x] Properly show Boolean / tinyint.
- [x] Abstract the date view using a helper function.
- [x] Fix PHP variables names with invalid characters, also escape SQL queries with `` to allow for all possible names.
- [x] Add support for nullable columns.
- [x] Make css/js files choosable
- [x] Generate enum select statically at creation.
- [x] Replace 0/1 with True False
- [x] Create an abstraction for the html syntax of columns in the read, edit and create pages.
- [x] Save the configuration (column names, checked checkboxes, etc) that were used to generate cruddiy last time.

## TODO
- [x] Move all config/validation to the top.?
- [x] Flexible columns on the index (Let the user choose which columns of the table are visible)
- [x] Allow custom queries for flexible records 
- [x] Bulk updating of records.
- [x] Remember cruddiy configuration
- [x] Show SQL Table comments.
- [x] Pre-fill create form, to duplicate records.
- [x] Advanced searching (remember search parameters) (Mail 8 mei van Klaas).
- [x] Advanced Ordering.
- [x] Split files into folder of the table name.
- [x] Update bootstrap to the newest version.
- [x] Multiple ordering displayed with 1st column, 2nd column.
- [x] Extend the previews recursively.
- [x] After creating, add a create another button (Move back button to the top). (Mail 16 juli van Klaas).
- [x] Add a possibility for custom extensions to the view pages.
- [ ] Automatic NAV-bar generation is broken. (WONTFIX)
- [x] For delete, check for external references before deletion.
- [x] Null filter toevoegen
- [x] ENUMs fatsoenlijk implementeren.
- [x] Knop back brengt je terug naar de index, de opties op de index worden goed onthouden.
- [x] Default values from SQL as defaults in create.


## Setup
Clone deze repository in de root van WaterWeb, het zit al in de gitignore, dus waterweb heeft hier geen last van.

## Usage
1. Ga naar je webhost/cruddiy, daar zijn instructies. 
2. Zodra je nieuwe bestanden hebt. Vanuit de root, gebruik de volgende commands om nieuw gegenereerde crud bestanden over te zetten (Dit overschrijft de huidige)
```
cp -r cruddiy/core/app/ cruddiy/core/temp/
rm  cruddiy/core/temp/config.php cruddiy/core/temp/error.php cruddiy/core/temp/helpers.php cruddiy/core/temp/index.php cruddiy/core/temp/navbar.php
#docker-compose exec php var/www/public/cruddiy/vendor/bin/php-cs-fixer fix var/www/public/cruddiy/core/temp
cp cruddiy/core/temp/* manager/modules/crud/
rm -r cruddiy/core/temp
```
3. Gebruik de [format files](https://marketplace.visualstudio.com/items?itemName=jbockle.jbockle-format-files) extensie voor VScode om de bestanden te formateren.
4. Let op dat je de [shared](core/shared) map ook moet overzetten.

### CRUDDIY
(FORK FROM CRUDDIY)
Cruddiy is a free **no-code**  PHP Bootstrap CRUD generator with foreign key support.

With Cruddiy you can easily generate some simple, but beautiful, PHP Bootstrap 4 CRUD pages (Create, Read, Update and Delete) with search, pagination and foreign key awareness.
