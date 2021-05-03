CREATE TABLE Pic (
    rowid INTEGER PRIMARY KEY AUTOINCREMENT,
    imgid VARCHAR,
    mime VARCHAR,
    author VARCHAR,
    description VARCHAR,
    license VARCHAR,
    path VARCHAR,
    orig_name VARCHAR,
    views INTEGER,
    stars INTEGER,
    dateposted DATE
);

----

CREATE UNIQUE INDEX Pic_imgid ON Pic(imgid);

