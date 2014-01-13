# Program Picker

The Program Picker was designed to simplify the process of choosing an appropriate exercise routine. Via a series of questions, it directs you to the most appropriate exercise program for your goal and experience level.

##Click logging
If you wish to use click logging, you'll need to set up the database schema as follows:

    CREATE TABLE IF NOT EXISTS `external_hits` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `path` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    );
    
    CREATE TABLE IF NOT EXISTS `internal_hits` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `path` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `path` (`path`)
    );
