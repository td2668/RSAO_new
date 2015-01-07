# Create my own users table...

DROP TABLE IF EXISTS rpb_users;
CREATE TABLE rpb_users SELECT * FROM users U WHERE U.browser = 1;
ALTER TABLE rpb_users ADD id int(10) FIRST;
UPDATE rpb_users SET id = user_id;
ALTER TABLE rpb_users ADD imageName varchar(32);
# UPDATE rpb_users U SET U.imageName = CONCAT(LOWER(U.last_name), "_", LOWER(U.first_name), ".jpg");
UPDATE rpb_users U SET U.imageName = CONCAT(LOWER(U.user_id), ".jpg");
ALTER TABLE rpb_users ADD PRIMARY KEY (id);

# Create my own user_topics_profiles table

DROP TABLE IF EXISTS rpb_user_topics_profile;
CREATE TABLE rpb_user_topics_profile SELECT * FROM user_topics_profile U WHERE U.user_id IN (SELECT id FROM rpb_users);
ALTER TABLE rpb_user_topics_profile ADD id int(10) FIRST;
UPDATE rpb_user_topics_profile SET id = user_topics_profile_id;
ALTER TABLE rpb_user_topics_profile ADD PRIMARY KEY (id);

# Create my own topics_research table

DROP TABLE IF EXISTS rpb_topics_research;
CREATE TABLE rpb_topics_research SELECT * FROM topics_research;
ALTER TABLE rpb_topics_research ADD id int(10) FIRST;
UPDATE rpb_topics_research SET id = topic_id;
ALTER TABLE rpb_topics_research ADD topic_name varchar(255);
UPDATE rpb_topics_research SET topic_name = name;
ALTER TABLE rpb_topics_research ADD PRIMARY KEY (id);
CREATE INDEX topic_name ON rpb_topics_research (topic_name);

# Create my own profiles table

DROP TABLE IF EXISTS rpb_profiles;
CREATE TABLE rpb_profiles SELECT * FROM profiles P WHERE P.user_id IN (SELECT id FROM rpb_users);

ALTER TABLE rpb_profiles ADD id int(10) FIRST;
UPDATE rpb_profiles SET id = user_id;
# UPDATE rpb_profiles U SET U.profile_short = "No profile available yet, please check back soon." WHERE U.profile_short = "";
ALTER TABLE rpb_profiles ADD full_cv_link varchar(512) default "";
UPDATE rpb_profiles SET full_cv_link = CONCAT("http://research.mtroyal.ca/research.php?action=view&type=researchers&rid=",id) WHERE rpb_profiles.id NOT IN (SELECT user_id FROM users_hidden);
ALTER TABLE rpb_profiles ADD PRIMARY KEY (id);

# Create my own divisions table

DROP TABLE IF EXISTS rpb_divisions;
CREATE TABLE rpb_divisions SELECT * FROM divisions;
ALTER TABLE rpb_divisions ADD id int(10) FIRST;
UPDATE rpb_divisions SET id = division_id;
ALTER TABLE rpb_divisions ADD division_name varchar(255);
UPDATE rpb_divisions SET division_name = name;
ALTER TABLE rpb_divisions ADD PRIMARY KEY (id);
CREATE INDEX division_name ON rpb_divisions (division_name);

# Create my own departments table

DROP TABLE IF EXISTS rpb_departments;
CREATE TABLE rpb_departments SELECT * FROM departments;
ALTER TABLE rpb_departments ADD id int(10) FIRST;
UPDATE rpb_departments SET id = department_id;
ALTER TABLE rpb_departments ADD department_name varchar(255);
UPDATE rpb_departments SET department_name = name;
ALTER TABLE rpb_departments ADD PRIMARY KEY (id);
CREATE INDEX department_name ON rpb_departments (department_name);

# ALSO ADD TWO DEPARTMENTS: id = 0 + id = 25 -> No Faculty

INSERT INTO rpb_departments (id, department_id,name,division_id,chair,department_name) VALUES ('25', '25','No Department','0','0','No Department');
INSERT INTO rpb_departments (id, department_id,name,division_id,chair,department_name) VALUES ('0', '0','No Department','0','0','No Department');

# Build BelongsIn Table...

DROP TABLE IF EXISTS rpb_belongsin;

CREATE TABLE rpb_belongsin (parentDataTableName varchar(32) NOT NULL default '', parentDataFieldName varchar(32) NOT NULL default '', item_id int(11) NOT NULL default '0', sharedItem_id int(11) NOT NULL default '0', PRIMARY KEY  (parentDataTableName,parentDataFieldName,item_id,sharedItem_id)) ENGINE=MyISAM DEFAULT CHARSET=latin1;

# Topics to topic names:

# Links topics to their names
INSERT INTO rpb_belongsin (item_id, sharedItem_id) SELECT user_topics_profile_id, topic_id FROM rpb_user_topics_profile;

# Add the parent data table + field to the belongsin entry
UPDATE rpb_belongsin SET parentDataTableName='rpb_user_topics_profile', parentDataFieldName='topic_id' WHERE parentDataTableName = '' AND parentDataFieldName = '';

# Topics to users:

# Links users to their topics
INSERT INTO rpb_belongsin (item_id, sharedItem_id) SELECT user_id, user_topics_profile_id FROM rpb_user_topics_profile;

# Add the parent data table + field to the belongsin entry
UPDATE rpb_belongsin SET parentDataTableName='rpb_users', parentDataFieldName='topics' WHERE parentDataTableName = '' AND parentDataFieldName = '';

# Profiles to users:

# Links users to their profiles
INSERT INTO rpb_belongsin (item_id, sharedItem_id) SELECT user_id, user_id FROM rpb_profiles;

# Add the parent data table + field to the belongsin entry
UPDATE rpb_belongsin SET parentDataTableName='rpb_users', parentDataFieldName='profiles' WHERE parentDataTableName = '' AND parentDataFieldName = '';

# Departments to divisions:

# Links departments to divisions (department to faculty)
INSERT INTO rpb_belongsin (item_id, sharedItem_id) SELECT department_id, division_id FROM rpb_departments;

# Add the parent data table + field to the belongsin entry
UPDATE rpb_belongsin SET parentDataTableName='rpb_departments', parentDataFieldName='division_id' WHERE parentDataTableName = '' AND parentDataFieldName = '';

# Departments to users:

# Links departments to users
INSERT INTO rpb_belongsin (item_id, sharedItem_id) SELECT user_id, department_id FROM rpb_users;

# Add the parent data table + field to the belongsin entry
UPDATE rpb_belongsin SET parentDataTableName='rpb_users', parentDataFieldName='department_id' WHERE parentDataTableName = '' AND parentDataFieldName = '';

