CREATE TABLE IF NOT EXISTS suppress_emails (
  se_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  email  VARCHAR(255) NOT NULL,
  updated_ts CHAR(14) NOT NULL DEFAULT '',
  reason VARCHAR(255) NOT NULL DEFAULT '',
  status VARCHAR(255) NOT NULL DEFAULT '', 
  UNIQUE INDEX seed_idx (email),
  PRIMARY KEY (se_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

