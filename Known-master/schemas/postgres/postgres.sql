--
-- Base Known schema
--

--
-- Table structure for table config
--

CREATE TABLE IF NOT EXISTS config (
  uuid varchar(255) NOT NULL,
  _id varchar(32) NOT NULL,
  owner varchar(255) NOT NULL,
  entity_subtype varchar(64) NOT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  contents text NOT NULL,
  search text NOT NULL,
  PRIMARY KEY (uuid)
);
CREATE INDEX c__id ON config (_id);
CREATE INDEX c_owner ON config (owner);
CREATE INDEX c_entity_subtype ON config (entity_subtype);

-- --------------------------------------------------------

--
-- Table structure for table entities
--

CREATE TABLE IF NOT EXISTS entities (
  uuid varchar(255) NOT NULL,
  _id varchar(32) NOT NULL UNIQUE,
  owner varchar(255) NOT NULL,
  entity_subtype varchar(64) NOT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  contents text NOT NULL,
  search text NOT NULL,
  PRIMARY KEY (uuid)
);

CREATE INDEX e_owner ON entities (owner, created);
CREATE INDEX e_entity_subtype ON entities (entity_subtype);

-- FULL TEXT ?



-- --------------------------------------------------------

--
-- Table structure for table reader
--

CREATE TABLE IF NOT EXISTS reader (
  uuid varchar(255) NOT NULL,
  _id varchar(32) NOT NULL UNIQUE,
  owner varchar(255) NOT NULL,
  entity_subtype varchar(64) NOT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  contents text NOT NULL,
  search text NOT NULL,
  PRIMARY KEY (uuid)
);

CREATE INDEX r_owner ON reader (owner, created);
CREATE INDEX r_entity_subtype ON reader (entity_subtype);

-- --------------------------------------------------------

--
-- Table structure for table metadata
--

CREATE TABLE IF NOT EXISTS metadata (
  entity varchar(255) NOT NULL,
  _id varchar(32) NOT NULL,
  collection varchar(64) NOT NULL,
  name varchar(32) NOT NULL,
  value text NOT NULL
);


CREATE INDEX m_entity ON metadata (entity,name);
CREATE INDEX m_value ON metadata (value);
CREATE INDEX m_name ON metadata (name);
CREATE INDEX m_collection ON metadata (collection);
CREATE INDEX m__id ON metadata (_id);


-- --------------------------------------------------------

--
-- Table structure for table versions
--

CREATE TABLE IF NOT EXISTS versions (
  label varchar(32) NOT NULL,
  value varchar(10) NOT NULL,
  PRIMARY KEY (label)
);

DELETE FROM versions WHERE label = 'schema';
INSERT INTO versions VALUES('schema', '2015072801');

--
-- Session handling table
--

CREATE TABLE IF NOT EXISTS session (
    session_id varchar(255) NOT NULL,
    session_value text NOT NULL,
    session_time integer NOT NULL,
    PRIMARY KEY (session_id)
);
