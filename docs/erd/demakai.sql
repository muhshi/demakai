CREATE TABLE kbji2014s (
    id integer(11) NOT NULL AUTO_INCREMENT,
    sumber varchar(255),
    kode varchar(255),
    judul text,
    deskripsi text,
    contoh_lapangan text,
    level varchar(255),
    is_leaf tinyint(1) NOT NULL,
    sektor varchar(255),
    mongo_id varchar(255),
    payload text,
    embed_hash varchar(255),
    PRIMARY KEY(id)
)
CREATE TABLE kbli2020s (
    id integer(11) NOT NULL AUTO_INCREMENT,
    sumber varchar(255),
    kode varchar(255),
    judul text,
    deskripsi text,
    contoh_lapangan text,
    level varchar(255),
    is_leaf tinyint(1) NOT NULL,
    sektor varchar(255),
    mongo_id varchar(255),
    payload text,
    embed_hash varchar(255),
    PRIMARY KEY(id)
)
CREATE TABLE kbli2025s (
    id integer(11) NOT NULL AUTO_INCREMENT,
    sumber varchar(255),
    kode varchar(255),
    judul text,
    deskripsi text,
    contoh_lapangan text,
    level varchar(255),
    is_leaf tinyint(1) NOT NULL,
    sektor varchar(255),
    mongo_id varchar(255),
    payload text,
    embed_hash varchar(255),
    PRIMARY KEY(id)
)