# LiveLoad Database source control #

Keep track of DB changes and be able to deploy

## What is this repository for? ##

- This project will help us to keep track DB changes and to be able to deploy them into relevant DB

## How do I get set up? ##

- Make sure you have a schema called db_control with all the tables under [root]/Structure/db_control/Tables/

- Copy temp credentials file and enter the right db connection details
```
#!bash
$ cp [root]/app/credentials/credentials.template [root]/app/credentials/credentials

```


## How does it work? ##

### Initiation ###

* After cloned the project to local and completed the set up run init file

```
#!bash
$ php init.php

```

* Once initiation completed commit the new files and push it repo

* Initiation should create File structure as follows
* - [root]/Structure/EP_TX_DATA/Functions/Function1.sql
* - [root]/Structure/EP_TX_DATA/Procedures/Procedure1.sql
* - [root]/Structure/EP_TX_DATA/Tables/Table1.sql
* - [root]/Structure/EP_TX_DATA/Triggers/Trigger1.sql
* - [root]/Structure/EP_TX_DATA/Events/Trigger1.sql
* - [root]/Structure/EP_TX_DATA/Views/Trigger1.sql


### Migration ###

* To migrate changes to relevant DBs run migrate file


```
#!bash
$ php migrate.php

```

* What this does it finds out the latest changes to git repo and executes the changed Stored Procedure (for now)
* At first it compares latest commit with the previous one, after the first migration it will compare whatever has been written into DB and with latest commit in git repo
* If the Stored Procedure was already exist it backs up under **"_BACKUP"** directory in case if execution fails it revert back to what it was before

###How to add new files?###

* File name has to be the exactly same as the Stored Procedure name and it should end with **".sql"** file extension

```
#!bash
eg. Fees.sql

```

## Notes ##

** PLEASE NOTE THIS IS ONLY FOR STORED PROCEDURE FOR NOW, THE REST WILL FOLLOW**