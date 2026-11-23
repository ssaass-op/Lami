USE `lami_db`;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `audit_logs`;
TRUNCATE TABLE `messages`;
TRUNCATE TABLE `interests`;
TRUNCATE TABLE `matches_preferences`;
TRUNCATE TABLE `privacy_settings`;
TRUNCATE TABLE `profiles`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `status`, `created_at`) VALUES
(1, 'Admin Lami', 'admin@lami.com', '+977-9801234567', '$2y$10$d.Y4EeUsW.A.iAcgpXgOseOkOfr2YT0lizsEQujYGgFInvxHbhuda', 'admin', 'verified', NOW()),
(2, 'Aarav Sharma', 'aarav@gmail.com', '+977-9841111111', '$2y$10$7iiIiN/QwXnLSgAecfAiLeQaADJQr1zvCUqr2lGcM4at3mIlUpDBi', 'standard', 'verified', NOW()),
(3, 'Bipana Shrestha', 'bipana@gmail.com', '+977-9842222222', '$2y$10$7iiIiN/QwXnLSgAecfAiLeQaADJQr1zvCUqr2lGcM4at3mIlUpDBi', 'standard', 'verified', NOW()),
(4, 'Chirag Gurung', 'chirag@gmail.com', '+977-9843333333', '$2y$10$7iiIiN/QwXnLSgAecfAiLeQaADJQr1zvCUqr2lGcM4at3mIlUpDBi', 'standard', 'verified', NOW()),
(5, 'Deepa Adhikari', 'deepa@gmail.com', '+977-9844444444', '$2y$10$7iiIiN/QwXnLSgAecfAiLeQaADJQr1zvCUqr2lGcM4at3mIlUpDBi', 'standard', 'verified', NOW()),
(6, 'Elina Thapa', 'elina@gmail.com', '+977-9845555555', '$2y$10$7iiIiN/QwXnLSgAecfAiLeQaADJQr1zvCUqr2lGcM4at3mIlUpDBi', 'standard', 'pending', NOW()),
(7, 'Gaurav Raj', 'gaurav@gmail.com', '+977-9846666666', '$2y$10$7iiIiN/QwXnLSgAecfAiLeQaADJQr1zvCUqr2lGcM4at3mIlUpDBi', 'standard', 'verified', NOW());
INSERT INTO `profiles` (`user_id`, `gender`, `dob`, `religion`, `caste`, `education`, `profession`, `location`, `height`, `bio`, `income_range`, `marital_status`, `photo_path`, `identity_doc_path`) VALUES
(2, 'male', '1998-05-15', 'Hindu', 'Brahmin', 'Master of Business Administration', 'Financial Analyst', 'Kathmandu', 178.50, 'I am a warm, family-oriented person looking for a partner to build a beautiful life. Enjoy traveling and reading.', 'Rs. 10 Lakhs - 15 Lakhs', 'never_married', 'uploads/photos/aarav.jpg', NULL),
(3, 'female', '2000-08-20', 'Buddhist', 'Newar', 'Bachelor of Fine Arts', 'Graphic Designer', 'Lalitpur', 162.00, 'Creative, optimistic and loves art. I value respect, transparency, and a strong sense of humor.', 'Rs. 5 Lakhs - 10 Lakhs', 'never_married', 'uploads/photos/bipana.jpg', NULL),
(4, 'male', '1996-03-10', 'Buddhist', 'Gurung', 'Bachelor in Software Engineering', 'Software Engineer', 'Pokhara', 172.00, 'Introverted coder but loves trekking and nature. Seeking someone who appreciates the simple things in life.', 'Rs. 15 Lakhs - 25 Lakhs', 'never_married', 'uploads/photos/chirag.jpg', NULL),
(5, 'female', '1999-11-05', 'Hindu', 'Chhetri', 'M.Sc. in Nursing', 'Registered Nurse', 'Bhaktapur', 165.00, 'Empathetic and hard-working. Life is about second chances and learning. Looking for a mature relationship.', 'Rs. 8 Lakhs - 12 Lakhs', 'divorced', 'uploads/photos/deepa.jpg', NULL),
(6, 'female', '2001-01-25', 'Hindu', 'Chhetri', 'Bachelor of Business Studies', 'Bank Officer', 'Kathmandu', 158.00, 'Friendly, down to earth, family-oriented. I work in a commercial bank in Kathmandu.', 'Rs. 6 Lakhs - 9 Lakhs', 'never_married', NULL, 'uploads/identities/citizenship_elina.jpg'),
(7, 'male', '1994-12-12', 'Hindu', 'Madhesi', 'Doctor of Medicine (MD)', 'Pediatrician', 'Biratnagar', 175.00, 'Dedicated medical professional. Raising a beautiful world with optimism. Looking for an understanding partner.', 'Rs. 25 Lakhs+', 'widowed', NULL, NULL);
INSERT INTO `privacy_settings` (`user_id`, `show_phone`, `show_photo`, `show_income`) VALUES
(2, 1, 1, 1),
(3, 0, 1, 0),
(4, 1, 1, 1),
(5, 1, 0, 1), 
(6, 0, 1, 0),
(7, 1, 1, 0);
INSERT INTO `matches_preferences` (`user_id`, `min_age`, `max_age`, `preferred_religion`, `preferred_caste`, `preferred_location`, `preferred_education`, `preferred_marital_status`, `preferred_profession`, `min_height`, `max_height`, `preferred_income`) VALUES
(2, 22, 28, 'Hindu', 'Brahmin', 'Kathmandu', 'Bachelor', 'never_married', 'Graphic Designer', 150.00, 175.00, 'Rs. 5 Lakhs - 10 Lakhs'),
(3, 25, 32, 'Buddhist', 'Newar', 'Lalitpur', 'Bachelor', 'any', 'Software Engineer', 165.00, 185.00, 'Rs. 10 Lakhs - 15 Lakhs'),
(4, 23, 28, 'Buddhist', 'Gurung', 'Pokhara', 'Bachelor', 'never_married', 'Graphic Designer', 155.00, 170.00, 'Rs. 5 Lakhs - 10 Lakhs'),
(5, 26, 35, 'Hindu', 'Chhetri', 'Bhaktapur', 'Master', 'any', 'Doctor', 168.00, 185.00, 'Rs. 15 Lakhs - 25 Lakhs'),
(6, 24, 30, 'Hindu', 'Chhetri', 'Kathmandu', 'Bachelor', 'never_married', 'Financial Analyst', 165.00, 180.00, 'Rs. 10 Lakhs - 15 Lakhs'),
(7, 26, 32, 'Hindu', 'Madhesi', 'Biratnagar', 'Doctor', 'any', 'Nurse', 155.00, 172.00, 'Rs. 8 Lakhs - 12 Lakhs');
INSERT INTO `interests` (`id`, `sender_id`, `receiver_id`, `status`, `created_at`) VALUES
(1, 2, 3, 'pending', NOW() - INTERVAL 2 DAY),
(2, 4, 3, 'accepted', NOW() - INTERVAL 1 DAY),
(3, 7, 5, 'accepted', NOW() - INTERVAL 3 DAY),
(4, 5, 2, 'rejected', NOW() - INTERVAL 4 DAY);
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 3, 'Namaste Bipana, nice profile!', 1, NOW() - INTERVAL 12 HOUR),
(2, 3, 4, 'Namaste Chirag, thank you! Glad to connect.', 1, NOW() - INTERVAL 11 HOUR),
(3, 4, 3, 'Are you based in Kathmandu or Pokhara?', 0, NOW() - INTERVAL 10 HOUR),
(4, 7, 5, 'Hello Dr. Gaurav here, nice to connect with you.', 1, NOW() - INTERVAL 2 DAY),
(5, 5, 7, 'Hello Gaurav, glad to connect. Hope you are doing well.', 1, NOW() - INTERVAL 1 DAY);
