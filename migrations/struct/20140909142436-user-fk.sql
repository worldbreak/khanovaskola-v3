ALTER TABLE
  blocks
ADD
  CONSTRAINT FK_9E929223A76AB124 FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE
  schemas
ADD
  CONSTRAINT FK_9E929223A76AB125 FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
