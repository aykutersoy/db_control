# Database source control #

*Keep track of DB changes and be able to deploy*

## What is this repository for? ##

- *This project will help us to keep track DB changes and to be able to deploy them into relevant DB*

## How do I get set up? ##

- *Run setup script.*

- *This will*
  - *Create DB called db_control along with two tables, logs and migration*
  - *Copies template files to actual files*


```sh
$ php setup.php

```

- *Please make sure you entered the right db connection details credentials file*


## How does it work? ##

### Initiation ###

- *After cloned the project to local and completed the set up run init file*

```sh
$ php init.php

```

- *Once initiation completed commit the new files and push it repo*

- *Initiation should create File structure as follows*
  - .../Structure/{DB_NAME}/Functions/Function1.sql
  - .../Structure/{DB_NAME}/Procedures/Procedure1.sql
  - .../Structure/{DB_NAME}/Tables/Table1.sql
  - .../Structure/{DB_NAME}/Triggers/Trigger1.sql
  - .../Structure/{DB_NAME}/Events/Trigger1.sql
  - .../Structure/{DB_NAME}/Views/Trigger1.sql


- __*Initiation script can be run anytime that will bring the latest changes from DB into project*__
  - For instance, you have done changes directly into DB and concluded the development this initiation will bring the changes what you have done.


### Migration ###

- *To migrate changes to relevant DBs run migrate file*


```sh
$ php migrate.php

```

- *What this does is finds out the latest changes to git repo and executes the changed Stored Procedure (for now)*
- *At first it compares latest commit with the previous one, after the first migration it will compare whatever has been written into DB and with latest commit in git repo*
- *If the Stored Procedure was already exist it backs up under __"_BACKUP"__ directory in case if execution fails it revert back to what it was before*

###How to ignore certaion DBs?###

- *add new DBs if necessary to ignoredDBs file, it should look like as follow*

```json
{
    "ignoredDBs" :
    [
        "information_schema",
        "mysql",
        "performance_schema",
        "sys",
        "db_control"
    ]
}


```

###How to add new files?###

- *File name has to be the exactly same as the Stored Procedure name and it should end with __".sql"__ file extension*

```sh
eg. Fees.sql

```

## Notes ##

__PLEASE NOTE THIS IS ONLY FOR STORED PROCEDURE FOR NOW, THE REST WILL FOLLOW__
