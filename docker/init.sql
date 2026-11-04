-- Base de datos separada para los tests automatizados.
CREATE DATABASE IF NOT EXISTS construckit_test;
GRANT ALL PRIVILEGES ON construckit_test.* TO 'construckit'@'%';