ALTER TABLE
  answers
ADD
  CONSTRAINT FK_50D0C60640E556F5 FOREIGN KEY (blueprint_id) REFERENCES blueprints (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  answers
ADD
  CONSTRAINT FK_50D0C606A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

CREATE INDEX IDX_50D0C60640E556F5 ON answers (blueprint_id);

CREATE INDEX IDX_50D0C606A76ED395 ON answers (user_id);

ALTER TABLE
  badge_user_bridges
ADD
  CONSTRAINT FK_3B83999EF7A2C2FC FOREIGN KEY (badge_id) REFERENCES badges (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  badge_user_bridges
ADD
  CONSTRAINT FK_3B83999EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  badge_user_bridges
ADD
  CONSTRAINT FK_3B83999E29C1004E FOREIGN KEY (video_id) REFERENCES videos (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  badge_user_bridges
ADD
  CONSTRAINT FK_3B83999E40E556F5 FOREIGN KEY (blueprint_id) REFERENCES blueprints (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

CREATE INDEX IDX_3B83999EF7A2C2FC ON badge_user_bridges (badge_id);

CREATE INDEX IDX_3B83999EA76ED395 ON badge_user_bridges (user_id);

CREATE INDEX IDX_3B83999E29C1004E ON badge_user_bridges (video_id);

CREATE INDEX IDX_3B83999E40E556F5 ON badge_user_bridges (blueprint_id);

ALTER TABLE
  comments
ADD
  CONSTRAINT FK_5F9E962A9C24C622 FOREIGN KEY (inReplyTo_id) REFERENCES comments (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  comments
ADD
  CONSTRAINT FK_5F9E962AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  comments
ADD
  CONSTRAINT FK_5F9E962A29C1004E FOREIGN KEY (video_id) REFERENCES videos (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

CREATE INDEX IDX_5F9E962A9C24C622 ON comments (inReplyTo_id);

CREATE INDEX IDX_5F9E962AF675F31B ON comments (author_id);

CREATE INDEX IDX_5F9E962A29C1004E ON comments (video_id);

ALTER TABLE
  video_views
ADD
  CONSTRAINT FK_9E92952529C1004E FOREIGN KEY (video_id) REFERENCES videos (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  video_views
ADD
  CONSTRAINT FK_9E929525A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

CREATE INDEX IDX_9E92952529C1004E ON video_views (video_id);

CREATE INDEX IDX_9E929525A76ED395 ON video_views (user_id);
