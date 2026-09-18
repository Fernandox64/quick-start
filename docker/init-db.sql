-- Roda automaticamente so na primeira inicializacao do volume do MariaDB
-- (imagem oficial executa tudo em /docker-entrypoint-initdb.d/*.sql nesse
-- momento). MYSQL_DATABASE do docker-compose.yml so cria o banco do site
-- principal ("db") - os bancos extra do multisite de demonstracao
-- (dfis, demat, demed) sao criados aqui.
CREATE DATABASE IF NOT EXISTS dfis;
CREATE DATABASE IF NOT EXISTS demat;
CREATE DATABASE IF NOT EXISTS demed;
GRANT ALL PRIVILEGES ON dfis.* TO 'db'@'%';
GRANT ALL PRIVILEGES ON demat.* TO 'db'@'%';
GRANT ALL PRIVILEGES ON demed.* TO 'db'@'%';
FLUSH PRIVILEGES;
