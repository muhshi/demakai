CREATE TABLE field_example_submissions (
    id integer(11) NOT NULL AUTO_INCREMENT,
    type varchar(255) NOT NULL,
    kode varchar(255) NOT NULL,
    content text NOT NULL,
    status varchar(255) NOT NULL DEFAULT pending,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE notifications (

)
CREATE TABLE search_histories (
    id integer(11) NOT NULL AUTO_INCREMENT,
    query varchar(255) NOT NULL,
    results_count integer(11) NOT NULL,
    detected_type varchar(255),
    ip_address varchar(255),
    user_agent text,
    user_id integer(11),
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE users (
    id integer(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    email_verified_at datetime,
    password varchar(255) NOT NULL,
    remember_token varchar(255),
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
ALTER TABLE notifications ADD FOREIGN KEY (notifiable_id) REFERENCES users (id)
ALTER TABLE search_histories ADD FOREIGN KEY (user_id) REFERENCES users (id)
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