CREATE TABLE Config (
    rowid INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR,
    value VARCHAR
);

----

CREATE UNIQUE INDEX Config_name ON Config(name);

