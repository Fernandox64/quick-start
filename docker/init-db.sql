-- Roda automaticamente so na primeira inicializacao do volume do MariaDB
-- (imagem oficial executa tudo em /docker-entrypoint-initdb.d/*.sql nesse
-- momento). MYSQL_DATABASE do docker-compose.yml so cria o banco do site
-- principal ("db") - os bancos extra do multisite de demonstracao
-- (dfis, demat, demed) sao criados aqui.
CREATE DATABASE IF NOT EXISTS dfis;
CREATE DATABASE IF NOT EXISTS demat;
CREATE DATABASE IF NOT EXISTS demed;
CREATE DATABASE IF NOT EXISTS defil;
CREATE DATABASE IF NOT EXISTS delet;
CREATE DATABASE IF NOT EXISTS depro;
GRANT ALL PRIVILEGES ON dfis.* TO 'db'@'%';
GRANT ALL PRIVILEGES ON demat.* TO 'db'@'%';
GRANT ALL PRIVILEGES ON demed.* TO 'db'@'%';
GRANT ALL PRIVILEGES ON defil.* TO 'db'@'%';
GRANT ALL PRIVILEGES ON delet.* TO 'db'@'%';
GRANT ALL PRIVILEGES ON depro.* TO 'db'@'%';
CREATE DATABASE IF NOT EXISTS demet;
GRANT ALL PRIVILEGES ON demet.* TO 'db'@'%';
CREATE DATABASE IF NOT EXISTS desoc;
GRANT ALL PRIVILEGES ON desoc.* TO 'db'@'%';
CREATE DATABASE IF NOT EXISTS dequi;
GRANT ALL PRIVILEGES ON dequi.* TO 'db'@'%';
CREATE DATABASE IF NOT EXISTS decom;
GRANT ALL PRIVILEGES ON decom.* TO 'db'@'%';
CREATE DATABASE IF NOT EXISTS decivil;
GRANT ALL PRIVILEGES ON decivil.* TO 'db'@'%';
CREATE DATABASE IF NOT EXISTS deelet;
GRANT ALL PRIVILEGES ON deelet.* TO 'db'@'%';
CREATE DATABASE IF NOT EXISTS degeo;
GRANT ALL PRIVILEGES ON degeo.* TO 'db'@'%';
FLUSH PRIVILEGES;
