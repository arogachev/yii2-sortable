BEGIN TRANSACTION;

CREATE TABLE "tests" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" text NOT NULL
);

INSERT INTO "tests" ("id", "name") VALUES (1, 'Common test');
INSERT INTO "tests" ("id", "name") VALUES (2, 'Programming test');

CREATE TABLE "continuous_questions" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "test_id" integer NOT NULL,
  "sort" integer NOT NULL,
  "content" text NOT NULL,
  "is_active" integer NOT NULL DEFAULT '1',
  FOREIGN KEY ("test_id") REFERENCES "tests" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (1, 1, 1, 'What''s your name?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (2, 1, 2, 'Where are you from?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (3, 1, 3, 'How old are you?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (4, 1, 4, 'What''s your profession', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (5, 1, 5, 'What''s your hobby?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (6, 2, 1, 'What''s your programming experience?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (7, 2, 2, 'What programming languages do you know?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (8, 2, 3, 'What VCS do you use?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (9, 2, 4, 'How good you are as system administrator?', 1);
INSERT INTO "continuous_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (10, 2, 5, 'Do you have GitHub account?', 1);

CREATE TABLE "interval_questions" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "test_id" integer NOT NULL,
  "sort" integer NOT NULL,
  "content" text NOT NULL,
  "is_active" integer NOT NULL DEFAULT '1',
  FOREIGN KEY ("test_id") REFERENCES "tests" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (1, 1, 1000, 'What''s your name?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (2, 1, 2000, 'Where are you from?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (3, 1, 3000, 'How old are you?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (4, 1, 4000, 'What''s your profession', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (5, 1, 5000, 'What''s your hobby?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (6, 2, 1000, 'What''s your programming experience?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (7, 2, 2000, 'What programming languages do you know?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (8, 2, 3000, 'What VCS do you use?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (9, 2, 4000, 'How good you are as system administrator?', 1);
INSERT INTO "interval_questions" ("id", "test_id", "sort", "content", "is_active") VALUES (10, 2, 5000, 'Do you have GitHub account?', 1);

COMMIT;
