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