-- Local development seed data.
-- All seeded accounts use the password: demo
-- The application currently stores passwords as MD5 hashes.

SET NAMES utf8mb4;

INSERT IGNORE INTO `users`
    (`username`, `password`, `userid`, `userlevel`, `email`, `timestamp`, `first_name`, `last_name`, `bio`)
VALUES
    ('Admin', MD5('demo'), '00000000000000000000000000000001', 9, 'admin@example.test', 1735689600, 'Ada', 'Admin', 'Local development administrator.'),
    ('Moderator', MD5('demo'), '00000000000000000000000000000002', 5, 'moderator@example.test', 1735689601, 'Matas', 'Moderatorius', 'Local development moderator.'),
    ('Aiste', MD5('demo'), '00000000000000000000000000000003', 1, 'aiste@example.test', 1735689602, 'Aiste', 'Jonaitė', 'Loves photography and travel.'),
    ('Jonas', MD5('demo'), '00000000000000000000000000000004', 1, 'jonas@example.test', 1735689603, 'Jonas', 'Petraitis', 'Enjoys building small web projects.'),
    ('Greta', MD5('demo'), '00000000000000000000000000000005', 1, 'greta@example.test', 1735689604, 'Greta', 'Kazlauskaitė', 'Coffee, books, and city walks.'),
    ('Tomas', MD5('demo'), '00000000000000000000000000000006', 1, 'tomas@example.test', 1735689605, 'Tomas', 'Vaitkus', 'Learning PHP and MySQL.');

INSERT IGNORE INTO `social_posts` (`id`, `username`, `content`, `created_at`)
VALUES
    (1001, 'Admin', 'Welcome to the local development social network!', 1735776000),
    (1002, 'Moderator', 'The demo community is ready for testing.', 1735862400),
    (1003, 'Aiste', 'Sharing a photo from my weekend walk.', 1735948800),
    (1004, 'Jonas', 'I am experimenting with a new PHP project.', 1736035200),
    (1005, 'Greta', 'What are you reading this week?', 1736121600),
    (1006, 'Tomas', 'Does anyone have a good MySQL tip?', 1736208000);

INSERT IGNORE INTO `social_comments`
    (`id`, `post_id`, `username`, `content`, `created_at`)
VALUES
    (2001, 1001, 'Moderator', 'Thanks for setting this up!', 1735779600),
    (2002, 1001, 'Aiste', 'Looking forward to trying it out.', 1735783200),
    (2003, 1003, 'Greta', 'That sounds lovely.', 1735952400),
    (2004, 1004, 'Tomas', 'Good luck with the project!', 1736038800),
    (2005, 1005, 'Jonas', 'I recommend a good book about clean code.', 1736125200);

INSERT IGNORE INTO `social_follows` (`follower`, `followed`, `created_at`)
VALUES
    ('Moderator', 'Admin', 1735779600),
    ('Aiste', 'Admin', 1735783200),
    ('Aiste', 'Greta', 1735952400),
    ('Jonas', 'Aiste', 1736038800),
    ('Jonas', 'Tomas', 1736042400),
    ('Greta', 'Jonas', 1736125200),
    ('Tomas', 'Moderator', 1736211600),
    ('Admin', 'Moderator', 1736215200);

INSERT IGNORE INTO `social_reactions`
    (`post_id`, `username`, `reaction`, `created_at`)
VALUES
    (1001, 'Moderator', 'like', 1735779600),
    (1001, 'Aiste', 'like', 1735783200),
    (1002, 'Admin', 'like', 1735866000),
    (1003, 'Greta', 'love', 1735952400),
    (1003, 'Jonas', 'like', 1736038800),
    (1004, 'Tomas', 'like', 1736042400),
    (1005, 'Aiste', 'like', 1736125200),
    (1006, 'Admin', 'like', 1736215200);

INSERT IGNORE INTO `social_messages`
    (`id`, `sender`, `recipient`, `content`, `created_at`)
VALUES
    (3001, 'Admin', 'Moderator', 'Welcome to the moderator team.', 1735783200),
    (3002, 'Aiste', 'Greta', 'Want to compare book recommendations?', 1735956000),
    (3003, 'Jonas', 'Tomas', 'I found a useful MySQL resource.', 1736046000),
    (3004, 'Moderator', 'Admin', 'The seeded data looks good.', 1736218800);
