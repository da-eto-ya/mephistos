CREATE TABLE orders (
  id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  price BIGINT DEFAULT 0 NOT NULL,
  description LONGTEXT NOT NULL,
  customer_id INT NOT NULL,
  executor_id INT,
  status TINYINT DEFAULT 0 NOT NULL,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
  created_rand INT NOT NULL,
  executed TIMESTAMP
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  DEFAULT COLLATE utf8_general_ci;
CREATE INDEX status_created_created_rand_index ON orders (status, created, created_rand);
