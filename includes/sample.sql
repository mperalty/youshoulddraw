-- Sample database schema and starter data for You Should Draw

CREATE TABLE IF NOT EXISTS drawoptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL,
  theme VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS adminuser (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS generated_prompts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  base_class_id INTEGER NOT NULL,
  major_feature_id INTEGER NOT NULL,
  accessory1_id INTEGER,
  accessory2_id INTEGER,
  accessory3_id INTEGER,
  emotion_id INTEGER,
  pet_id INTEGER,
  prompt TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert a sample admin user with password "admin"
INSERT INTO adminuser (password) VALUES ('$2b$12$ejz0BS4cxVIVz3dT73rTDudkwwGgxAvYjktOcC6TsWDAk/NIOvyJm');

-- Basic starter options
INSERT INTO drawoptions (name, type, theme) VALUES
  ('Knight', 'Base Class', 'Medieval'),
  ('Wizard', 'Base Class', 'Fantasy'),
  ('Robot', 'Base Class', 'Sci-Fi'),
  ('Wings', 'Major Feature', 'Fantasy'),
  ('Laser Eyes', 'Major Feature', 'Sci-Fi'),
  ('Glowing Sword', 'Accessories', 'Medieval'),
  ('Magic Staff', 'Accessories', 'Fantasy'),
  ('Jetpack', 'Accessories', 'Sci-Fi'),
  ('Happy', 'Emotion', NULL),
  ('Angry', 'Emotion', NULL),
  ('Dragon', 'Pet', 'Fantasy'),
  ('Robot Dog', 'Pet', 'Sci-Fi');
