-- SQLite port of db/schema.sql, for in-memory test containers only.
-- Column set matches production; MySQL-only clauses (ENGINE, CHARSET,
-- COMMENT, AUTO_INCREMENT) are dropped since SQLite doesn't use them.

CREATE TABLE beers (
  beer_id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  degree INTEGER,
  link TEXT
);

CREATE TABLE pubs (
  pub_id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  mark REAL,
  markVoted INTEGER NOT NULL,
  beerMark REAL,
  beerMarkVoted INTEGER NOT NULL,
  beerPriceVoted INTEGER NOT NULL,
  wineMark REAL,
  wineMarkVoted INTEGER NOT NULL,
  winePrice REAL,
  winePriceVoted INTEGER NOT NULL,
  foodMark REAL,
  foodMarkVoted INTEGER NOT NULL,
  foodPrice REAL,
  foodPriceVoted INTEGER NOT NULL,
  toaletsMark REAL,
  interierMark REAL,
  exterierMark REAL,
  serviceMark REAL,
  overallMark REAL,
  location TEXT NOT NULL,
  address TEXT NOT NULL,
  latitude REAL NOT NULL,
  longitude REAL NOT NULL,
  hidden INTEGER NOT NULL,
  inserted DATETIME NOT NULL,
  updated DATETIME NOT NULL,
  type TEXT,
  long_name TEXT NOT NULL,
  opening_hours TEXT,
  website TEXT,
  whole_name TEXT NOT NULL,
  user_id INTEGER
);

CREATE TABLE pub_descriptions (
  description_id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  version INTEGER NOT NULL,
  text TEXT,
  pub_id INTEGER
);

CREATE TABLE ratings (
  rating_id INTEGER PRIMARY KEY AUTOINCREMENT,
  pub_id INTEGER,
  user_id INTEGER,
  date DATETIME NOT NULL,
  wine_criteria REAL,
  wine_price REAL,
  food_criteria REAL,
  food_price_criteria REAL,
  toalets_criteria REAL,
  service_criteria REAL,
  overall_criteria REAL,
  interier_criteria REAL,
  exterier_criteria REAL,
  name TEXT,
  garden INTEGER,
  calculated INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE rating_beer (
  beer_id INTEGER NOT NULL,
  rating_id INTEGER NOT NULL,
  beer_criteria REAL,
  beer_price REAL,
  PRIMARY KEY (beer_id, rating_id)
);

CREATE TABLE users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  email TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  "right" TEXT NOT NULL,
  name TEXT,
  click DATETIME NOT NULL,
  skin INTEGER NOT NULL,
  ip TEXT NOT NULL,
  registration DATETIME NOT NULL,
  state INTEGER NOT NULL
);
