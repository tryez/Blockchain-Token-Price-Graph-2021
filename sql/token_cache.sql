


CREATE TABLE IF NOT EXISTS `token_cache` (
  `key` varchar(255) PRIMARY KEY,
  `value` longtext NOT NULL,
  `expiration` int NOT NULL
);