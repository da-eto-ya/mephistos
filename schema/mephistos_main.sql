CREATE TABLE users (
  id            INT PRIMARY KEY                     NOT NULL AUTO_INCREMENT,
  username      VARCHAR(255)                        NOT NULL,
  password_hash VARCHAR(255)                        NOT NULL,
  balance       BIGINT DEFAULT 0                    NOT NULL,
  avatar        VARCHAR(225) DEFAULT ''             NOT NULL,
  role          TINYINT DEFAULT 0                   NOT NULL,
  created       TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  DEFAULT COLLATE utf8_general_ci;
CREATE UNIQUE INDEX name_index ON users (username);
