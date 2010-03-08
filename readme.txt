Basic library Usage
----------------------------------

1.) Go to config file and fill in database connection info
2.) Set write permissions on root library directory
3.) Include or run main.php

NOTE: The first time main.php is ran it will crawl through
the defined database in the config file and generate all base
model file templates. This is a good place to start, but once
your application starts requiring advanced associations you will
need to go through each model file and define those associations that
could not be automatically resolved.

The model files will be placed inside the model directory upon
sucess of the rake script. From which point you can begin leveraging
the true power of the entire library. No need to include model files
or define a connection as this is all handled internally by the library.

If you need to access the connection object once instantiated you
may do so by calling ActiveRecord::getConnection() which returns
the PDO instance used to connect to the defined database in the config.

Additional
-----------------------------------

Table names should be plural:

IE. blogs NOT blog

Model classes will be singular:

User::find() NOT Users::find()

----------------------------------
* More documentation to come