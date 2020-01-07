\connect scrutinizer
\i tests/simpletest/data/config.sql

DROP DATABASE :dbname;
DROP DATABASE spikesource1;
DROP DATABASE spikesource2;

DROP ROLE :superuser;
DROP ROLE :poweruser;
DROP ROLE :guestuser;
